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

use Monolog;

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
    const DEBUG = Monolog\Logger::DEBUG;

    /**
     * Interesting events e.g. User logs in, SQL logs.
     */
    const INFO = Monolog\Logger::INFO;

    /**
     * Uncommon events
     */
    const NOTICE = Monolog\Logger::NOTICE;

    /**
     * Exceptional occurrences that are not errors e.g. Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
     */
    const WARNING = Monolog\Logger::WARNING;

    /**
     * Runtime errors
     */
    const ERROR = Monolog\Logger::ERROR;

    /**
     * Critical conditions e.g. Application component unavailable, unexpected exception.
     */
    const CRITICAL = Monolog\Logger::CRITICAL;

    /**
     * Action must be taken immediately e.g. Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
     */
    const ALERT = Monolog\Logger::ALERT;

    /**
     * Urgent alert.
     */
    const EMERGENCY = Monolog\Logger::EMERGENCY;

    /**
     * @var array List of valid log levels
     */
    protected $logTypes = array(
        self::DEBUG     => 'debug',
        self::INFO      => 'info',
        self::NOTICE    => 'notice',
        self::WARNING   => 'warning',
        self::ERROR     => 'error',
        self::CRITICAL  => 'critical',
        self::ALERT     => 'alert',
        self::EMERGENCY => 'emergency'
    );

    /**
     * @var \Monolog\Logger The monolog instance
     */
    protected $instance = null;

    /**
     * Create a Monolog\Logger instance and attach a handler
     *
     * @see    https://github.com/Seldaek/monolog#handlers Monolog handlers documentation
     * @param array $handlers Array of Monolog handlers
     */
    public function __construct(array $handlers)
    {
        $this->instance = new \Monolog\Logger('resque');

        foreach ($handlers as $handler) {
            $this->instance->pushHandler($handler);
        }
    }

    /**
     * Return a Monolog Logger instance
     *
     * @return \Monolog\Logger instance, ready to use
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Send log message to output interface
     *
     * @param string $message Message to output
     * @param int    $context Some context around the log
     * @param int    $logType The log type
     * @reutrn mixed
     */
    public function log($message, $context = null, $logType = null)
    {
        if (is_int($context) and is_null($logType)) {
            $logType = $context;
            $context = array();
        }

        if (!is_array($context)) {
            $context = is_null($context) ? array() : array($context);
        }

        if (!is_int($logType)) {
            $logType = self::INFO;
        }

        return call_user_func(array($this->instance, $this->logTypes[$logType]), $message, $context);
    }
}
