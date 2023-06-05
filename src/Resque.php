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

/**
 * Main Resque class
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 *
 * @method static string            redisKey(?string $queue = null, ?string $suffix = null)          Get the Queue key.
 * @method static \Resque\Job       job(string $id)                                                  Get a job by id.
 * @method static \Resque\Job       push($job, ?array $data = null, ?string $queue = null)           Push a new job onto the queue.
 * @method static \Resque\Job       later($job, ?array $data = null, ?string $queue = null)          Queue a job for later retrieval.
 * @method static \Resque\Job|false pop(array $queues, int $timeout = 10, bool $blocking = true)     Pop the next job off of the queue.
 * @method static int               size(string $queue) Get the size (number of pending jobs)        of the specified queue.
 * @method static int               sizeDelayed(string $queue) Get the size (number of delayed jobs) of the specified queue.
 * @method static string            getQueue(?string $queue)                                         Get the queue or return the default.
 * @method static void              loadConfig(string $file = self::DEFAULT_CONFIG_FILE)             Read and load data from a config file
 * @method static void              setConfig(array $config)                                         Set the configuration array
 */
class Resque
{
    /**
     * php-resque version
     */
    public const VERSION = '4.0.0';

    /**
     * @var Queue The queue instance.
     */
    protected static $queue = null;

    /**
     * Create a queue instance.
     *
     * @return Queue
     */
    public static function queue(): Queue
    {
        if (!static::$queue) {
            static::$queue = new Queue();
        }

        return static::$queue;
    }

    /**
     * Set the queue instance.
     *
     * @param Queue $queue The queue instance
     *
     * @return void
     */
    public static function setQueue(Queue $queue): void
    {
        static::$queue = $queue;
    }

    /**
     * Dynamically pass calls to the default connection.
     *
     * @param string $method     The method to call
     * @param array  $parameters The parameters to pass
     */
    public static function __callStatic(string $method, array $parameters)
    {
        // Simplify the call to setConfig and loadConfig
        if (in_array($method, ['setConfig', 'loadConfig'])) {
            return call_user_func_array([Config::class, $method], $parameters);
        }

        return call_user_func_array([static::queue(), $method], $parameters);
    }

    /**
     * Gets Resque stats
     *
     * @return array
     */
    public static function stats(): array
    {
        return Redis::instance()->hgetall('stats');
    }
}
