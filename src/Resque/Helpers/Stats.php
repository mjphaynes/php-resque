<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Helpers;

use Resque\Redis;

/**
 * Stats recording
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Stats
{
    const DEFAULT_KEY = 'stats';

    /**
     * Get the value of the supplied statistic counter for the specified statistic
     *
     * @param  string $stat The name of the statistic to get the stats for
     * @param  string $key  The stat key to use
     * @return mixed  Value of the statistic.
     */
    public static function get($stat, $key = Stats::DEFAULT_KEY)
    {
        return (int)Redis::instance()->hget($key, $stat);
    }

    /**
     * Increment the value of the specified statistic by a certain amount (default is 1)
     *
     * @param  string $stat The name of the statistic to increment
     * @param  int    $by   The amount to increment the statistic by
     * @param  string $key  The stat key to use
     * @return bool   True if successful, false if not.
     */
    public static function incr($stat, $by = 1, $key = Stats::DEFAULT_KEY)
    {
        return (bool)Redis::instance()->hincrby($key, $stat, $by);
    }

    /**
     * Decrement the value of the specified statistic by a certain amount (default is -1)
     *
     * @param  string $stat The name of the statistic to decrement.
     * @param  int    $by   The amount to decrement the statistic by.
     * @param  string $key  The stat key to use
     * @return bool   True if successful, false if not.
     */
    public static function decr($stat, $by = 1, $key = Stats::DEFAULT_KEY)
    {
        return (bool)Redis::instance()->hincrby($key, $stat, -1 * $by);
    }

    /**
     * Delete a statistic with the given name.
     *
     * @param  string $stat The name of the statistic to delete.
     * @param  string $key  The stat key to use
     * @return bool   True if successful, false if not.
     */
    public static function clear($stat, $key = Stats::DEFAULT_KEY)
    {
        return (bool)Redis::instance()->hdel($key, $stat);
    }
}
