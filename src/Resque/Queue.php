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

use Resque\Helpers\Stats;

/**
 * Resque queue class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Queue
{

    /**
     * @var Redis The Redis instance
     */
    protected $redis;

    /**
     * @var string The name of the default queue
     */
    protected $default;

    /**
     * Get the Queue key
     *
     * @param  Queue|null  $queue  the worker to get the key for
     * @param  string|null $suffix to be appended to key
     * @return string
     */
    public static function redisKey($queue = null, $suffix = null)
    {
        if (is_null($queue)) {
            return 'queues';
        }

        return (strpos($queue, 'queue:') !== 0 ? 'queue:' : '').$queue.($suffix ? ':'.$suffix : '');
    }

    /**
     * Create a new queue instance
     *
     * @param string $default Name of default queue to add job to
     */
    public function __construct($default = null)
    {
        $this->redis = Redis::instance();

        $this->default = $default ?: \Resque::getConfig('default.jobs.queue', 'default');
    }

    /**
     * Get a job by id
     *
     * @param  string $id Job id
     * @return Job    job instance
     */
    public function job($id)
    {
        return Job::load($id);
    }

    /**
     * Push a new job onto the queue
     *
     * @param  string $job   The job class
     * @param  mixed  $data  The job data
     * @param  string $queue The queue to add the job to
     * @return Job    job instance
     */
    public function push($job, array $data = null, $queue = null)
    {
        if (false !== ($delay = \Resque::getConfig('default.jobs.delay', false))) {
            return $this->later($delay, $job, $data, $queue);
        }

        return Job::create($this->getQueue($queue), $job, $data);
    }

    /**
     * Queue a job for later retrieval. Jobs are unique per queue and
     * are deleted upon retrieval. If a given job (payload) already exists,
     * it is updated with the new delay.
     *
     * @param  int    $delay This can be number of seconds or unix timestamp
     * @param  string $job   The job class
     * @param  mixed  $data  The job data
     * @param  string $queue The queue to add the job to
     * @return Job    job instance
     */
    public function later($delay, $job, array $data = array(), $queue = null)
    {
        // If it's a datetime object conver to unix time
        if ($delay instanceof \DateTime) {
            $delay = $delay->getTimestamp();
        }

        if (!is_numeric($delay)) {
            throw new \InvalidArgumentException('The delay "'.$delay.'" must be an integer or DateTime object.');
        }

        // If the delay is smaller than 3 years then assume that an interval
        // has been passed i.e. 600 seconds, otherwise it's a unix timestamp
        if ($delay < 94608000) {
            $delay += time();
        }

        return Job::create($this->getQueue($queue), $job, $data, $delay);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  array     $queues   Queues to watch for new jobs
     * @param  int       $timeout  Timeout if blocking
     * @param  bool      $blocking Should Redis use blocking
     * @return Job|false
     */
    public function pop(array $queues, $timeout = 10, $blocking = true)
    {
        $queue = $payload = null;

        foreach ($queues as $key => $queue) {
            $queues[$key] = self::redisKey($queue);
        }

        if ($blocking) {
            list($queue, $payload) = $this->redis->blpop($queues, $timeout);
            $queue = $this->redis->removeNamespace($queue);
        } else {
            foreach ($queues as $queue) {
                if ($payload = $this->redis->lpop($queue)) {
                    break;
                }
            }
        }

        if (!$queue or !$payload) {
            return false;
        }

        $queue = substr($queue, strlen('queue:'));

        return Job::loadPayload($queue, $payload);
    }

    /**
     * Return the size (number of pending jobs) of the specified queue.
     *
     * @param  string $queue name of the queue to be checked for pending jobs
     * @return int    The size of the queue.
     */
    public function size($queue)
    {
        return $this->redis->llen(self::redisKey($this->getQueue($queue)));
    }

    /**
     * Return the size (number of delayed jobs) of the specified queue.
     *
     * @param  string $queue name of the queue to be checked for delayed jobs
     * @return int    The size of the delayed queue.
     */
    public function sizeDelayed($queue)
    {
        return $this->redis->zcard(self::redisKey($this->getQueue($queue), 'delayed'));
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null $queue Name of queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return $queue ?: $this->default;
    }
}
