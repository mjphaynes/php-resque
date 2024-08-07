<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque;

use Resque\Helpers\ClosureJob;
use Resque\Helpers\Stats;

/**
 * Resque job class
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class Job
{
    // Job status constants
    public const STATUS_WAITING   = 1;
    public const STATUS_DELAYED   = 2;
    public const STATUS_RUNNING   = 3;
    public const STATUS_COMPLETE  = 4;
    public const STATUS_CANCELLED = 5;
    public const STATUS_FAILED    = 6;

    /**
     * Job ID length
     */
    public const ID_LENGTH = 22;

    /**
     * @var Redis The Redis instance
     */
    protected $redis;

    /**
     * @var string The name of the queue that this job belongs to
     */
    protected $queue;

    /**
     * @var array|string The payload sent through for this job
     */
    protected $payload;

    /**
     * @var string The ID of this job
     */
    protected $id;

    /**
     * @var string The classname this job
     */
    protected $class;

    /**
     * @var string The method name for this job
     */
    protected $method = 'perform';

    /**
     * @var array|null|\Closure The data/arguments for the job
     */
    protected $data;

    /**
     * @var Worker Instance of the worker running this job
     */
    protected $worker;

    /**
     * @var object Instance of the class performing work for this job
     */
    protected $instance;

    /**
     * @var array of statuses that are considered final/complete
     */
    protected static $completeStatuses = [
        self::STATUS_FAILED,
        self::STATUS_COMPLETE,
        self::STATUS_CANCELLED,
    ];

    /**
     * Get the Redis key
     *
     * @param Job|int $job    the job to get the key for
     * @param string  $suffix to be appended to key
     *
     * @return string
     */
    public static function redisKey($job, ?string $suffix = null): string
    {
        $id = $job instanceof Job ? $job->id : $job;
        return 'job:'.$id.($suffix ? ':'.$suffix : '');
    }

    /**
     * Create a new job and save it to the specified queue.
     *
     * @param string          $queue  The name of the queue to place the job in
     * @param string|callable $class  The name of the class that contains the code to execute the job
     * @param array|null      $data   Any optional arguments that should be passed when the job is executed
     * @param int             $run_at Unix timestamp of when to run the job to delay execution
     *
     * @return Job|null
     */
    public static function create(string $queue, $class, ?array $data = null, int $run_at = 0): ?self
    {
        $id = static::createId($queue, $class, $data, $run_at);

        $job = new static($queue, $id, $class, $data);

        if ($run_at > 0) {
            if (!$job->delay($run_at)) {
                return null;
            }
        } elseif (!$job->queue()) {
            return null;
        }

        Stats::incr('total', 1);
        Stats::incr('total', 1, Queue::redisKey($queue, 'stats'));

        return $job;
    }

    /**
     * Create a new job id
     *
     * @param string          $queue  The name of the queue to place the job in
     * @param string|callable $class  The name of the class that contains the code to execute the job
     * @param array           $data   Any optional arguments that should be passed when the job is executed
     * @param int             $run_at Unix timestamp of when to run the job to delay execution
     *
     * @return string
     */
    public static function createId(string $queue, $class, ?array $data = null, int $run_at = 0): string
    {
        $id = dechex(crc32($queue)).
            dechex((int)(microtime(true) * 1000)).
            md5(json_encode($class).json_encode($data).$run_at.uniqid('', true));

        return substr($id, 0, self::ID_LENGTH);
    }

    /**
     * Load a job from id
     *
     * @param string $id The job id
     *
     * @return static|null
     */
    public static function load(string $id): ?self
    {
        $packet = Redis::instance()->hgetall(self::redisKey($id));

        if (empty($packet) or empty($packet['queue']) or !count($payload = json_decode($packet['payload'], true))) {
            return null;
        }

        return new static($packet['queue'], $payload['id'], $payload['class'], $payload['data']);
    }

    /**
     * Load a job from the Redis payload
     *
     * @param string $queue   The name of the queue to place the job in
     * @param string $payload The payload that was stored in Redis
     *
     * @return static
     */
    public static function loadPayload(string $queue, string $payload): self
    {
        $payload = json_decode($payload, true);

        if (!is_array($payload) or !count($payload)) {
            throw new \InvalidArgumentException('Supplied $payload must be a json encoded array.');
        }

        return new static($queue, $payload['id'], $payload['class'], $payload['data']);
    }

    /**
     * Create a new job
     *
     * @param string          $queue Queue to add job to
     * @param string          $id    Job id
     * @param string|callable $class Job class to run
     * @param array|null      $data  Any Job data
     */
    public function __construct(string $queue, string $id, $class, ?array $data = null)
    {
        $this->redis = Redis::instance();

        if (!is_string($queue) or empty($queue)) {
            throw new \InvalidArgumentException('The Job queue "('.gettype($queue).')'.$queue.'" must a non-empty string');
        }

        $this->queue = $queue;
        $this->id    = $id;
        $this->data  = $data;

        if ($class instanceof \Closure) {
            $this->class = ClosureJob::class;
            $this->data  = $class;
        } else {
            $this->class = $class;
            if (strpos($this->class, '@')) {
                [$this->class, $this->method] = explode('@', $this->class, 2);
            }

            // Remove any spaces or back slashes
            $this->class = trim($this->class, '\\ ');
        }

        $this->payload = $this->createPayload();

        Event::fire(Event::JOB_INSTANCE, $this);
    }

    /**
     * Generate a string representation of this object
     *
     * @return string Representation of the current job status class
     */
    public function __toString()
    {
        return sprintf(
            '%s:%s#%s(%s)',
            $this->queue,
            $this->class,
            $this->id,
            empty($this->data) ? '' : json_encode($this->data)
        );
    }

    /**
     * Save the job to Redis queue
     *
     * @return bool success
     */
    public function queue(): bool
    {
        if (Event::fire(Event::JOB_QUEUE, $this) === false) {
            return false;
        }

        $this->redis->sadd(Queue::redisKey(), $this->queue);
        $status = $this->redis->rpush(Queue::redisKey($this->queue), $this->payload);

        if ($status < 1) {
            return false;
        }

        $this->setStatus(self::STATUS_WAITING);

        Stats::incr('queued', 1);
        Stats::incr('queued', 1, Queue::redisKey($this->queue, 'stats'));

        Event::fire(Event::JOB_QUEUED, $this);

        return true;
    }

    /**
     * Save the job to Redis delayed queue
     *
     * @param int $time unix time of when to perform job
     *
     * @return bool success
     */
    public function delay(int $time): bool
    {
        if (Event::fire(Event::JOB_DELAY, [$this, $time]) === false) {
            return false;
        }

        $this->redis->sadd(Queue::redisKey(), $this->queue);
        $status = $this->redis->zadd(Queue::redisKey($this->queue, 'delayed'), $time, $this->payload);

        if ($status < 1) {
            return false;
        }

        $this->setStatus(self::STATUS_DELAYED);
        $this->redis->hset(self::redisKey($this), 'delayed', $time);

        Stats::incr('delayed', 1);
        Stats::incr('delayed', 1, Queue::redisKey($this->queue, 'stats'));

        Event::fire(Event::JOB_DELAYED, [$this, $time]);

        return true;
    }

    /**
     * Perform the job
     *
     * @return bool
     */
    public function perform(): bool
    {
        Stats::decr('queued', 1);
        Stats::decr('queued', 1, Queue::redisKey($this->queue, 'stats'));

        if (Event::fire(Event::JOB_PERFORM, $this) === false) {
            $this->cancel();
            return false;
        }

        $this->run();

        $retval = true;

        try {
            $instance = $this->getInstance();

            ob_start();

            if (method_exists($instance, 'setUp')) {
                $instance->setUp($this);
            }

            call_user_func_array([$instance, $this->method], [$this->data, $this]);

            if (method_exists($instance, 'tearDown')) {
                $instance->tearDown($this);
            }

            $this->complete();
        } catch (Exception\Cancel $e) {
            // setUp said don't perform this job
            $this->cancel();
            $retval = false;
        } catch (\Exception $e) {
            $this->fail($e);
            $retval = false;
        }

        $output = ob_get_contents();

        while (ob_get_length()) {
            ob_end_clean();
        }

        $this->redis->hset(self::redisKey($this), 'output', $output);

        Event::fire(Event::JOB_DONE, $this);

        return $retval;
    }

    /**
     * Get the instantiated object for this job that will be performing work
     *
     * @return object Instance of the object that this job belongs to
     */
    public function getInstance(): object
    {
        if (!is_null($this->instance)) {
            return $this->instance;
        }

        if (!class_exists($this->class)) {
            throw new \RuntimeException('Could not find job class "'.$this->class.'"');
        }
        if (!method_exists($this->class, $this->method)) {
            throw new \RuntimeException('Job class "'.$this->class.'" does not contain a public "'.$this->method.'" method');
        }

        $class = new \ReflectionClass($this->class);

        if ($class->isAbstract()) {
            throw new \RuntimeException('Job class "'.$this->class.'" cannot be an abstract class');
        }

        $instance = $class->newInstance();

        return $this->instance = $instance;
    }

    /**
     * Mark the current job running
     */
    public function run(): void
    {
        $this->setStatus(Job::STATUS_RUNNING);

        $this->redis->zadd(Queue::redisKey($this->queue, 'running'), time(), $this->payload);
        Stats::incr('running', 1);
        Stats::incr('running', 1, Queue::redisKey($this->queue, 'stats'));

        Event::fire(Event::JOB_RUNNING, $this);
    }

    /**
     * Mark the current job stopped
     * This is an internal function as the job is either completed, cancelled or failed
     */
    protected function stopped(): void
    {
        $this->redis->zrem(Queue::redisKey($this->queue, 'running'), $this->payload);

        Stats::decr('running', 1);
        Stats::decr('running', 1, Queue::redisKey($this->queue, 'stats'));
    }

    /**
     * Mark the current job as complete
     */
    public function complete(): void
    {
        $this->stopped();

        $this->setStatus(Job::STATUS_COMPLETE);

        $this->redis->zadd(Queue::redisKey($this->queue, 'processed'), time(), $this->payload);
        Stats::incr('processed', 1);
        Stats::incr('processed', 1, Queue::redisKey($this->queue, 'stats'));

        Event::fire(Event::JOB_COMPLETE, $this);
    }

    /**
     * Mark the current job as cancelled
     */
    public function cancel(): void
    {
        $this->stopped();

        $this->setStatus(Job::STATUS_CANCELLED);

        $this->redis->zadd(Queue::redisKey($this->queue, 'cancelled'), time(), $this->payload);
        Stats::incr('cancelled', 1);
        Stats::incr('cancelled', 1, Queue::redisKey($this->queue, 'stats'));

        Event::fire(Event::JOB_CANCELLED, $this);
    }

    /**
     * Mark the current job as having failed
     *
     * @param \Exception $e
     */
    public function fail(\Exception $e): void
    {
        $this->stopped();

        $this->setStatus(Job::STATUS_FAILED, $e);

        // For the failed jobs we store a lot more data for debugging
        $packet = $this->getPacket();
        $failed_payload = array_merge(json_decode($this->payload, true), [
            'worker'    => $packet['worker'],
            'started'   => $packet['started'],
            'finished'  => $packet['finished'],
            'output'    => $packet['output'],
            'exception' => (array)json_decode($packet['exception'], true),
        ]);
        $this->redis->zadd(Queue::redisKey($this->queue, 'failed'), time(), json_encode($failed_payload));
        Stats::incr('failed', 1);
        Stats::incr('failed', 1, Queue::redisKey($this->queue, 'stats'));

        Event::fire(Event::JOB_FAILURE, [$this, $e]);
    }

    /**
     * Returns the fail error for the job
     *
     * @return string
     */
    public function failError(): string
    {
        if (
            ($packet = $this->getPacket()) and
            $packet['status'] !== Job::STATUS_FAILED and
            ($e = json_decode($packet['exception'], true))
        ) {
            return $e['error'];
        }

        return 'Unknown exception';
    }

    /**
     * Create a payload string from the given job and data
     *
     * @return string
     */
    protected function createPayload(): string
    {
        if ($this->data instanceof \Closure) {
            $closure = serialize(new Helpers\SerializableClosure($this->data));
            $data = compact('closure');
        } else {
            $data = $this->data;
        }

        return json_encode(['id' => $this->id, 'class' => $this->class, 'data' => $data]);
    }

    /**
     * Update the status indicator for the current job with a new status
     *
     * @param int        $status The status of the job
     * @param \Exception $e      If failed status it sends through exception
     */
    public function setStatus(int $status, ?\Exception $e = null): void
    {
        if (!($packet = $this->getPacket())) {
            $packet = [
                'id'        => $this->id,
                'queue'     => $this->queue,
                'payload'   => $this->payload,
                'worker'    => '',
                'status'    => $status,
                'created'   => microtime(true),
                'updated'   => microtime(true),
                'delayed'   => 0,
                'started'   => 0,
                'finished'  => 0,
                'output'    => '',
                'exception' => null,
            ];
        }

        $packet['worker']  = (string)$this->worker;
        $packet['status']  = $status;
        $packet['updated'] = microtime(true);

        if ($status == Job::STATUS_RUNNING) {
            $packet['started'] = microtime(true);
        }

        if (in_array($status, self::$completeStatuses)) {
            $packet['finished'] = microtime(true);
        }

        if ($e) {
            $packet['exception'] = json_encode([
                'class'     => get_class($e),
                'error'     => sprintf('%s in %s on line %d', $e->getMessage(), $e->getFile(), $e->getLine()),
                'backtrace' => explode("\n", $e->getTraceAsString()),
            ]);
        }

        $this->redis->hmset(self::redisKey($this), $packet);

        // Expire the status for completed jobs
        if (in_array($status, self::$completeStatuses)) {
            $this->redis->expire(self::redisKey($this), Config::read('default.expiry_time', Config::DEFAULT_EXPIRY_TIME));
        }
    }

    /**
     * Fetch the packet for the job being monitored.
     *
     * @return array|bool
     */
    public function getPacket()
    {
        if ($packet = $this->redis->hgetall(self::redisKey($this))) {
            return $packet;
        }

        return false;
    }

    /**
     * Fetch the status for the job
     *
     * @return int|bool
     */
    public function getStatus()
    {
        if ($packet = $this->getPacket()) {
            if (isset($packet['status'])) {
                return (int)$packet['status'];
            }
        }

        return false;
    }

    /**
     * Returns formatted execution time string
     *
     * @return string
     */
    public function execTime(): string
    {
        $packet = $this->getPacket();

        if ($packet['finished'] === 0) {
            throw new \Exception('The job has not yet ran');
        }

        return $packet['finished'] - $packet['started'];
    }

    /**
     * Returns formatted execution time string
     *
     * @return string
     */
    public function execTimeStr(): string
    {
        $execTime = $this->execTime();

        if ($execTime >= 1) {
            return round($execTime, 1).'s';
        } else {
            return round($execTime * 1000, 2).'ms';
        }
    }

    /**
     * Get the job id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the job id.
     *
     * @throws \RuntimeException
     */
    public function setId(): void
    {
        throw new \RuntimeException('It is not possible to set job id, you must create a new job');
    }

    /**
     * Get the job queue.
     *
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * Set the job queue.
     *
     * @throws \RuntimeException
     */
    public function setQueue(): void
    {
        throw new \RuntimeException('It is not possible to set job queue, you must create a new job');
    }

    /**
     * Get the job class.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Set the job class.
     *
     * @throws \RuntimeException
     */
    public function setClass(): void
    {
        throw new \RuntimeException('It is not possible to set job class, you must create a new job');
    }

    /**
     * Get the job data.
     *
     * @return array|null|\Closure
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the job data.
     *
     * @throws \RuntimeException
     */
    public function setData(): void
    {
        throw new \RuntimeException('It is not possible to set job data, you must create a new job');
    }

    /**
     * Get the job delayed time
     *
     * @return int
     */
    public function getDelayedTime(): int
    {
        $packet = $this->getPacket();

        if ($packet['delayed'] > 0) {
            return $packet['delayed'];
        }

        return -1;
    }

    /**
     * Get the queue worker interface
     *
     * @return Worker
     */
    public function getWorker(): Worker
    {
        return $this->worker;
    }

    /**
     * Set the queue worker interface
     *
     * @param Worker $worker
     */
    public function setWorker(Worker $worker): void
    {
        $this->worker = $worker;
    }

    /**
     * Return array representation of this job
     *
     * @return array
     */
    public function toArray(): array
    {
        $packet = $this->getPacket();

        return [
            'id'        => (string)$this->id,
            'queue'     => (string)$this->queue,
            'class'     => (string)$this->class,
            'data'      => $this->data,
            'worker'    => (string)$packet['worker'],
            'status'    => (int)$packet['status'],
            'created'   => (float)$packet['created'],
            'updated'   => (float)$packet['updated'],
            'delayed'   => (float)$packet['delayed'],
            'started'   => (float)$packet['started'],
            'finished'  => (float)$packet['finished'],
            'output'    => $packet['output'],
            'exception' => $packet['exception'],
        ];
    }

    /**
     * Look for any jobs which are running but the worker is dead.
     * Meaning that they are also not running but left in limbo
     *
     * This is a form of garbage collection to handle cases where the
     * server may have been killed and the workers did not die gracefully
     * and therefore leave state information in Redis.
     *
     * @param array $queues list of queues to check
     */
    public static function cleanup(array $queues = ['*']): array
    {
        $cleaned = ['zombie' => 0, 'processed' => 0];
        $redis = Redis::instance();

        if (in_array('*', $queues)) {
            $queues = (array)$redis->smembers(Queue::redisKey());
            sort($queues);
        }

        $workers = $redis->smembers(Worker::redisKey());

        foreach ($queues as $queue) {
            $jobs = $redis->zrangebyscore(Queue::redisKey($queue, 'running'), 0, time());

            foreach ($jobs as $payload) {
                $job = self::loadPayload($queue, $payload);
                $packet = $job->getPacket();

                if (!in_array($packet['worker'], $workers)) {
                    $job->fail(new Exception\Zombie());

                    $cleaned['zombie']++;
                }
            }

            $cleaned['processed'] = $redis->zremrangebyscore(Queue::redisKey($queue, 'processed'), 0, time() - Config::read('default.expiry_time', Config::DEFAULT_EXPIRY_TIME));
        }

        return $cleaned;
    }

    /**
     * Update the Delayed Job Status to STATUS_CANCELLED manually
     *
     * @param mixed $jobId
     */
    public static function cancelJob($jobId): void
    {
        if ($jobId != '') {
            Redis::instance()->hset(self::redisKey($jobId), 'status', self::STATUS_CANCELLED);
        }
    }
}
