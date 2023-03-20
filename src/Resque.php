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

use Resque\Redis;
use Resque\Queue;

/**
 * Main Resque class
 *
 * @package Resque
 * @author Michael Haynes
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
