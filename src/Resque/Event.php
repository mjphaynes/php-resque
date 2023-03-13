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
 * Resque event/hook system class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Event
{
    // Worker event constants
    public const WORKER_INSTANCE       = 100;
    public const WORKER_STARTUP        = 101;
    public const WORKER_SHUTDOWN       = 102;
    public const WORKER_FORCE_SHUTDOWN = 103;
    public const WORKER_REGISTER       = 104;
    public const WORKER_UNREGISTER     = 105;
    public const WORKER_WORK           = 106;
    public const WORKER_FORK           = 107;
    public const WORKER_FORK_ERROR     = 108;
    public const WORKER_FORK_PARENT    = 109;
    public const WORKER_FORK_CHILD     = 110;
    public const WORKER_WORKING_ON     = 111;
    public const WORKER_DONE_WORKING   = 112;
    public const WORKER_KILLCHILD      = 113;
    public const WORKER_PAUSE          = 114;
    public const WORKER_RESUME         = 115;
    public const WORKER_WAKEUP         = 116;
    public const WORKER_CLEANUP        = 117;
    public const WORKER_LOW_MEMORY     = 118;
    public const WORKER_CORRUPT        = 119;

    // Job event constants
    public const JOB_INSTANCE       = 200;
    public const JOB_QUEUE          = 201;
    public const JOB_QUEUED         = 202;
    public const JOB_DELAY          = 203;
    public const JOB_DELAYED        = 204;
    public const JOB_QUEUE_DELAYED  = 205;
    public const JOB_QUEUED_DELAYED = 206;
    public const JOB_PERFORM        = 207;
    public const JOB_RUNNING        = 208;
    public const JOB_COMPLETE       = 209;
    public const JOB_CANCELLED      = 210;
    public const JOB_FAILURE        = 211;
    public const JOB_DONE           = 212;

    /**
     * @var array containing all registered callbacks, indexed by event name
     */
    protected static array $events = [];

    /**
     * Listen in on a given event to have a specified callback fired.
     *
     * @param  string|array $event    Name of event to listen on.
     * @param  callable     $callback Any callback callable by call_user_func_array
     * @return void
     */
    public static function listen($event, callable $callback): void
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
            self::$events[$event] = [];
        }

        self::$events[$event][] = $callback;
    }

    /**
     * Raise a given event with the supplied data.
     *
     * @param  string $event Name of event to be raised
     * @param  mixed  $data  Data that should be passed to each callback (optional)
     * @return bool
     */
    public static function fire(string $event, $data = null): bool
    {
        if (!is_array($data)) {
            $data = [$data];
        }
        array_unshift($data, $event);

        $retval = true;

        foreach (['*', $event] as $e) {
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
     * @param  string   $event    Name of event
     * @param  callable $callback The callback as defined when listen() was called
     * @return true
     */
    public static function forget(string $event, callable $callback)
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
    public static function clear(): void
    {
        self::$events = [];
    }

    /**
     * Returns the name of the given event from constant
     *
     * @param  int          $event Event constant
     * @return string|false
     */
    public static function eventName(int $event)
    {
        static $constants = null;

        if (is_null($constants)) {
            $class = new \ReflectionClass('Resque\Event');

            $constants = [];
            foreach ($class->getConstants() as $name => $value) {
                $constants[$value] = strtolower($name);
            }
        }

        return $constants[$event] ?? false;
    }
}
