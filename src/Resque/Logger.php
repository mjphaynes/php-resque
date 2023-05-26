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

use Monolog\Logger as Monolog;

/**
 * Resque logger class. Wrapper for Monolog (https://github.com/Seldaek/monolog)
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Logger
{
    /**
     * Detailed debug information
     */
    public const DEBUG = Monolog::DEBUG;

    /**
     * Interesting events e.g. User logs in, SQL logs.
     */
    public const INFO = Monolog::INFO;

    /**
     * Uncommon events
     */
    public const NOTICE = Monolog::NOTICE;

    /**
     * Exceptional occurrences that are not errors e.g. Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
     */
    public const WARNING = Monolog::WARNING;

    /**
     * Runtime errors
     */
    public const ERROR = Monolog::ERROR;

    /**
     * Critical conditions e.g. Application component unavailable, unexpected exception.
     */
    public const CRITICAL = Monolog::CRITICAL;

    /**
     * Action must be taken immediately e.g. Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
     */
    public const ALERT = Monolog::ALERT;

    /**
     * Urgent alert.
     */
    public const EMERGENCY = Monolog::EMERGENCY;

    /**
     * @var array List of valid log levels
     */
    protected array $logTypes = [
        self::DEBUG     => 'debug',
        self::INFO      => 'info',
        self::NOTICE    => 'notice',
        self::WARNING   => 'warning',
        self::ERROR     => 'error',
        self::CRITICAL  => 'critical',
        self::ALERT     => 'alert',
        self::EMERGENCY => 'emergency',
    ];

    /**
     * @var Monolog The monolog instance
     */
    protected Monolog $instance;

    /**
     * Create a Monolog instance and attach a handler
     *
     * @see    https://github.com/Seldaek/monolog#handlers Monolog handlers documentation
     * @param array $handlers Array of Monolog handlers
     */
    public function __construct(array $handlers)
    {
        $this->instance = new Monolog('resque');

        foreach ($handlers as $handler) {
            $this->instance->pushHandler($handler);
        }
    }

    /**
     * Return a Monolog Logger instance
     *
     * @return Monolog instance, ready to use
     */
    public function getInstance(): Monolog
    {
        return $this->instance;
    }

    /**
     * Send log message to output interface
     *
     * @param string    $message Message to output
     * @param mixed     $context Some context around the log
     * @param int|null  $logType The log type
     *
     * @return mixed
     */
    public function log(string $message, $context = null, ?int $logType = null)
    {
        if (is_int($context) and is_null($logType)) {
            $logType = $context;
            $context = [];
        }

        if (!is_array($context)) {
            $context = is_null($context) ? [] : [$context];
        }

        if (!in_array($logType, $this->instance->getLevels())) {
            $logType = Monolog::INFO;
        }

        return call_user_func([$this->instance, $this->logTypes[$logType]], $message, $context);
    }
}
