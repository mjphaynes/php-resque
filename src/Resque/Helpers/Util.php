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

/**
 * Resque Utilities
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Util
{

    /**
     * Returns human readable sizes. Based on original functions written by
     * [Aidan Lister](http://aidanlister.com/repos/v/function.size_readable.php)
     * and [Quentin Zervaas](http://www.phpriot.com/d/code/strings/filesize-format/).
     *
     * @param  int    $bytes      size in bytes
     * @param  string $force_unit a definitive unit
     * @param  string $format     the return string format
     * @param  bool   $si         whether to use SI prefixes or IEC
     * @return string
     */
    public static function bytes($bytes, $force_unit = null, $format = null, $si = true)
    {
        $format = ($format === null) ? '%01.2f %s' : (string) $format;

        // IEC prefixes (binary)
        if ($si == false or strpos($force_unit, 'i') !== false) {
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
            $mod = 1024;

            // SI prefixes (decimal)
        } else {
            $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
            $mod = 1000;
        }

        if (($power = array_search((string) $force_unit, $units)) === false) {
            $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
        }

        return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
    }

    /**
     * Constants for human_time_diff()
     */
    const MINUTE_IN_SECONDS = 60;
    const HOUR_IN_SECONDS   = 3600;
    const DAY_IN_SECONDS    = 86400;
    const WEEK_IN_SECONDS   = 604800;
    const YEAR_IN_SECONDS   = 3.15569e7;

    /**
     * Determines the difference between two timestamps.
     *
     * The difference is returned in a human readable format such as "1 hour",
     * "5 mins", "2 days".
     *
     * @param  int    $from Unix timestamp from which the difference begins.
     * @param  int    $to   Optional. Unix timestamp to end the time difference. Default becomes time() if not set.
     * @return string Human readable time difference.
     */
    public static function human_time_diff($from, $to = null)
    {
        $to = $to ?: time();

        $diff = (int)abs($to - $from);

        if ($diff < self::MINUTE_IN_SECONDS) {
            $since = array($diff, 'sec');
        } elseif ($diff < self::HOUR_IN_SECONDS) {
            $since = array(round($diff / self::MINUTE_IN_SECONDS), 'min');
        } elseif ($diff < self::DAY_IN_SECONDS and $diff >= self::HOUR_IN_SECONDS) {
            $since = array(round($diff / self::HOUR_IN_SECONDS), 'hour');
        } elseif ($diff < self::WEEK_IN_SECONDS and $diff >= self::DAY_IN_SECONDS) {
            $since = array(round($diff / self::DAY_IN_SECONDS), 'day');
        } elseif ($diff < 30 * self::DAY_IN_SECONDS and $diff >= self::WEEK_IN_SECONDS) {
            $since = array(round($diff / self::WEEK_IN_SECONDS), 'week');
        } elseif ($diff < self::YEAR_IN_SECONDS and $diff >= 30 * self::DAY_IN_SECONDS) {
            $since = array(round($diff / (30 * self::DAY_IN_SECONDS)), 'month');
        } elseif ($diff >= self::YEAR_IN_SECONDS) {
            $since = array(round($diff / self::YEAR_IN_SECONDS), 'year');
        }

        if ($since[0] <= 1) {
            $since[0] = 1;
        }

        return $since[0].' '.$since[1].($since[0] == 1 ? '' : 's');
    }

    /**
     * Gets a value from an array using a dot separated path.
     * Returns true if found and false if not.
     *
     * @param  array  $array     array to search
     * @param  mixed  $path      key path string (delimiter separated) or array of keys
     * @param  mixed  $found     value that was found
     * @param  string $delimiter key path delimiter
     * @return bool
     */
    public static function path($array, $path, &$found, $delimiter = '.')
    {
        if (!is_array($array)) {
            return false;
        }

        if (is_array($path)) {
            $keys = $path;
        } else {
            if (array_key_exists($path, $array)) {
                $found = $array[$path]; // No need to do extra processing
                return true;
            }

            $keys = explode($delimiter, trim($path, "{$delimiter} "));
        }

        do {
            $key = array_shift($keys);

            if (ctype_digit($key)) {
                $key = (int)$key;
            }

            if (isset($array[$key])) {
                if ($keys) {
                    if (is_array($array[$key])) {
                        $array = $array[$key];
                    } else {
                        break;
                    }
                } else {
                    $found = $array[$key];
                    return true;
                }
            } else {
                break;
            }
        } while ($keys);

        // Unable to find the value requested
        return false;
    }
}
