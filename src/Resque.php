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
 * @author Michael Haynes
 *
 * @method static string            redisKey(?string $queue = null, ?string $suffix = null)          Get the Queue key.
 * @method static \Resque\Job       job(string $id)                                                  Get a job by id.
 * @method static \Resque\Job       push($job, ?array $data = null, ?string $queue = null)           Push a new job onto the queue.
 * @method static \Resque\Job       later($job, ?array $data = null, ?string $queue = null)          Queue a job for later retrieval.
 * @method static \Resque\Job|false pop(array $queues, int $timeout = 10, bool $blocking = true)     Pop the next job off of the queue.
 * @method static int               size(string $queue) Get the size (number of pending jobs)        of the specified queue.
 * @method static int               sizeDelayed(string $queue) Get the size (number of delayed jobs) of the specified queue.
 * @method static string            getQueue(?string $queue)                                         Get the queue or return the default.
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
    protected static ?Queue $queue = null;

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
     * Dynamically pass calls to the default connection.
     *
     * @param string $method     The method to call
     * @param array  $parameters The parameters to pass
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        $callable = [static::queue(), $method];

        return call_user_func_array($callable, $parameters);
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
