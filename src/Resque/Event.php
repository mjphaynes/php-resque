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
 * Resque event/hook system class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Event
{

    // Worker event constants
    const WORKER_INSTANCE       = 100;
    const WORKER_STARTUP        = 101;
    const WORKER_SHUTDOWN       = 102;
    const WORKER_FORCE_SHUTDOWN = 103;
    const WORKER_REGISTER       = 104;
    const WORKER_UNREGISTER     = 105;
    const WORKER_WORK           = 106;
    const WORKER_FORK           = 107;
    const WORKER_FORK_ERROR     = 108;
    const WORKER_FORK_PARENT    = 109;
    const WORKER_FORK_CHILD     = 110;
    const WORKER_WORKING_ON     = 111;
    const WORKER_DONE_WORKING   = 112;
    const WORKER_KILLCHILD      = 113;
    const WORKER_PAUSE          = 114;
    const WORKER_RESUME         = 115;
    const WORKER_WAKEUP         = 116;
    const WORKER_CLEANUP        = 117;
    const WORKER_LOW_MEMORY     = 118;
    const WORKER_CORRUPT        = 119;

    // Job event constants
    const JOB_INSTANCE       = 200;
    const JOB_QUEUE          = 201;
    const JOB_QUEUED         = 202;
    const JOB_DELAY          = 203;
    const JOB_DELAYED        = 204;
    const JOB_QUEUE_DELAYED  = 205;
    const JOB_QUEUED_DELAYED = 206;
    const JOB_PERFORM        = 207;
    const JOB_RUNNING        = 208;
    const JOB_COMPLETE       = 209;
    const JOB_CANCELLED      = 210;
    const JOB_FAILURE        = 211;
    const JOB_DONE           = 212;

    /**
     * @var array containing all registered callbacks, indexed by event name
     */
    protected static $events = array();

    /**
     * Listen in on a given event to have a specified callback fired.
     *
     * @param  string $event    Name of event to listen on.
     * @param  mixed  $callback Any callback callable by call_user_func_array
     * @return true
     */
    public static function listen($event, $callback)
    {
        if (is_array($event)) {
            foreach ($event as $e) {
                self::listen($e, $callback);
            }
            return;
        }

        if ($event !== '*' and !self::eventName($event)) {
            throw new \InvalidArgumentException('Event "'.$event.'" is not a valid event');
        }

        if (!isset(self::$events[$event])) {
            self::$events[$event] = array();
        }

        self::$events[$event][] = $callback;
    }

    /**
     * Raise a given event with the supplied data.
     *
     * @param  string $event Name of event to be raised
     * @param  mixed  $data  Data that should be passed to each callback (optional)
     * @return true
     */
    public static function fire($event, $data = null)
    {
        if (!is_array($data)) {
            $data = array($data);
        }
        array_unshift($data, $event);

        $retval = true;

        foreach (array('*', $event) as $e) {
            if (!array_key_exists($e, self::$events)) {
                continue;
            }

            foreach (self::$events[$e] as $callback) {
                if (!is_callable($callback)) {
                    continue;
                }

                if (($retval = call_user_func_array($callback, $data)) === false) {
                    break 2;
                }
            }
        }

        return $retval !== false;
    }

    /**
     * Stop a given callback from listening on a specific event.
     *
     * @param  string $event    Name of event
     * @param  mixed  $callback The callback as defined when listen() was called
     * @return true
     */
    public static function forget($event, $callback)
    {
        if (!isset(self::$events[$event])) {
            return true;
        }

        $key = array_search($callback, self::$events[$event]);

        if ($key !== false) {
            unset(self::$events[$event][$key]);
        }

        return true;
    }

    /**
     * Clear all registered listeners.
     */
    public static function clear()
    {
        self::$events = array();
    }

    /**
     * Returns the name of the given event from constant
     *
     * @param  int          $event Event constant
     * @return string|false
     */
    public static function eventName($event)
    {
        static $constants = null;

        if (is_null($constants)) {
            $class = new \ReflectionClass('Resque\Event');

            $constants = array();
            foreach ($class->getConstants() as $name => $value) {
                $constants[$value] = strtolower($name);
            }
        }

        return isset($constants[$event]) ? $constants[$event] : false;
    }
}
