<?php
/**
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Resque\Job;
use Resque\Event;
use Resque\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/*
  This is an example for a basic autoloader for the example jobs.
  You probably want to have your own autoloader (or use composer)
  that autoloads your job class files when they're called from php-resque

  There is access to the following variables from here:
  - $config  array            An array of configuration options parsed from config file and options
  - $input   InputInterface   Console input interface for retreiving user option
  - $output  OutputInterface  Console output interface for sending messages to user
  - $logger  Logger           The logger instance, you should log messages to this with the `log()` method
 */
class ExampleAutoloader
{
    protected $logger;

    protected $job = null;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;

        $this->registerEvents();
        $this->registerAutoload();
    }

    // If you're wondering what this is about run `worker:start` with `-vvv` (very verbose)
    // which outputs all the debugging logs
    public function registerEvents()
    {
        $job = & $this->job;

        Event::listen(Event::JOB_PERFORM, function ($event, $_job) use (&$job) {
            $job = $_job;
        });
    }

    public function registerAutoload()
    {
        $job = & $this->job;
        $logger = $this->logger;

        spl_autoload_register(function ($class) use (&$job, $logger) {
            $isJobClass = $job instanceof Job and $job->getClass() == trim($class, ' \\');

            if (file_exists($file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php')) {
                $logMessage = sprintf('Including job %s class %s file %s in pid:%s', $job, $class, $file, getmypid());
                $isJobClass and $logger->log($logMessage, Logger::DEBUG);

                require_once $file;
            } else {
                $logMessage = sprintf(
                    'Job %s class %s file was not found, tried: %s in pid:%s',
                    $job,
                    $class,
                    $file,
                    getmypid()
                );
                $isJobClass and $logger->log($logMessage, Logger::DEBUG);
            }
        });
    }
}

new ExampleAutoloader($logger);
