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
use Resque\Helpers\Output;

/**
 * Resque host class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Host
{

    /**
     * @var Redis The Redis instance
     */
    protected $redis;

    /**
     * @var string The hostname
     */
    protected $hostname;

    /**
     * @var int Host key timeout
     */
    protected $timeout = 120;

    /**
     * Get the Redis key
     *
     * @param  Host   $host   The host to get the key for
     * @param  string $suffix To be appended to key
     * @return string
     */
    public static function redisKey($host = null, $suffix = null)
    {
        if (is_null($host)) {
            return 'hosts';
        }

        $hostname = $host instanceof Host ? $host->hostname : $host;
        return 'host:'.$hostname.($suffix ? ':'.$suffix : '');
    }

    /**
     * Create a new host
     * @param null|mixed $hostname
     */
    public function __construct($hostname = null)
    {
        $this->redis    = Redis::instance();
        $this->hostname = $hostname ?: gethostname();
    }

    /**
     * Generate a string representation of this worker.
     *
     * @return string Identifier for this worker instance.
     */
    public function __toString()
    {
        return $this->hostname;
    }

    /**
     * Mark host as having an active worker
     *
     * @param Worker $worker the worker instance
     */
    public function working(Worker $worker)
    {
        $this->redis->sadd(self::redisKey(), $this->hostname);

        $this->redis->sadd(self::redisKey($this), (string)$worker);
        $this->redis->expire(self::redisKey($this), $this->timeout);
    }

    /**
     * Mark host as having a finished worker
     *
     * @param Worker $worker The worker instance
     */
    public function finished(Worker $worker)
    {
        $this->redis->srem(self::redisKey($this), (string)$worker);
    }

    /**
     * Cleans up any dead hosts
     *
     * @return array List of cleaned hosts
     */
    public function cleanup()
    {
        $hosts   = $this->redis->smembers(self::redisKey());
        $workers = $this->redis->smembers(Worker::redisKey());
        $cleaned = array('hosts' => array(), 'workers' => array());

        foreach ($hosts as $hostname) {
            $host = new static($hostname);

            if (!$this->redis->exists(self::redisKey($host))) {
                $this->redis->srem(self::redisKey(), (string)$host);
                $cleaned['hosts'][] = (string)$host;
            } else {
                $host_workers = $this->redis->smembers(self::redisKey($host));

                foreach ($host_workers as $host_worker) {
                    if (!in_array($host_worker, $workers)) {
                        $cleaned['workers'][] = $host_worker;

                        $this->redis->srem(self::redisKey($host), $host_worker);
                    }
                }
            }
        }

        return $cleaned;
    }
}
