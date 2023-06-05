<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Console\Command;

use Resque\Config;
use Resque\Event;
use Resque\Helpers\Util;
use Resque\Logger;
use Resque\Redis;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Main Command class
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var Logger The logger instance
     */
    protected $logger;

    /**
     * @var array<string, mixed> Config array
     */
    protected $config = [];

    /**
     * @var array Config to options mapping
     */
    protected $configOptionMap = [
        'include'        => 'include',
        'scheme'         => 'redis.scheme',
        'host'           => 'redis.host',
        'port'           => 'redis.port',
        'namespace'      => 'redis.namespace',
        'password'       => 'redis.password',
        'verbose'        => 'default.verbose',
        'queue'          => 'default.jobs.queue',
        'delay'          => 'default.jobs.delay',
        'queue'          => 'default.workers.queue',
        'blocking'       => 'default.workers.blocking',
        'interval'       => 'default.workers.interval',
        'timeout'        => 'default.workers.timeout',
        'memory'         => 'default.workers.memory',
        'log'            => 'log',
        'listenhost'     => 'socket.listen.host',
        'listenport'     => 'socket.listen.port',
        'listenretry'    => 'socket.listen.retry',
        'listentimeout'  => 'socket.listen.timeout',
        'connecthost'    => 'socket.connect.host',
        'connectport'    => 'socket.connect.port',
        'connecttimeout' => 'socket.connect.timeout',
        'json'           => 'socket.json',
    ];

    /**
     * Globally sets some input options that are available for all commands
     *
     * @param  array $definitions List of command definitions
     * @return array
     */
    protected function mergeDefinitions(array $definitions): array
    {
        return array_merge(
            $definitions,
            [
                new InputOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file. Inline options override.', Config::DEFAULT_CONFIG_FILE),
                new InputOption('include', 'I', InputOption::VALUE_OPTIONAL, 'Path to include php file'),
                new InputOption('host', 'H', InputOption::VALUE_OPTIONAL, 'The Redis hostname.', Redis::DEFAULT_HOST),
                new InputOption('port', 'p', InputOption::VALUE_OPTIONAL, 'The Redis port.', Redis::DEFAULT_PORT),
                new InputOption('scheme', null, InputOption::VALUE_REQUIRED, 'The Redis scheme to use.', Redis::DEFAULT_SCHEME),
                new InputOption('namespace', null, InputOption::VALUE_REQUIRED, 'The Redis namespace to use. This is prefixed to all keys.', Redis::DEFAULT_NS),
                new InputOption('password', null, InputOption::VALUE_OPTIONAL, 'The Redis AUTH password.'),
                new InputOption('log', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Specify the handler(s) to use for logging.'),
                new InputOption('events', 'e', InputOption::VALUE_NONE, 'Outputs all events to the console, for debugging.'),
            ]
        );
    }

    /**
     * Initialises the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialised based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->parseConfig($input->getOptions(), $this->getNativeDefinition()->getOptionDefaults());
        $config = $this->getConfig();

        // Configure Redis
        Redis::setConfig([
            'scheme'    => $config['scheme'],
            'host'      => $config['host'],
            'port'      => $config['port'],
            'namespace' => $config['namespace'],
            'password'  => $config['password'],
        ]);

        // Set the verbosity
        if (array_key_exists('verbose', $config)) {
            if (!$input->getOption('verbose') and !$input->getOption('quiet') and is_int($config['verbose'])) {
                $output->setVerbosity($config['verbose']);
            } else {
                $this->config['verbose'] = $output->getVerbosity();
            }
        }

        // Set the monolog loggers, it's possible to speficfy multiple handlers
        $logs = array_key_exists('log', $config) ? array_unique($config['log']) : [];
        empty($logs) and $logs[] = 'console';

        $handlerConnector = new \Resque\Logger\Handler\Connector($this, $input, $output);

        $handlers = [];
        foreach ($logs as $log) {
            $handlers[] = $handlerConnector->resolve($log);
        }

        $this->logger = new Logger($handlers);

        // Unset some variables so as not to pass to include file
        unset($logs, $handlerConnector, $handlers);

        // Include file?
        if (array_key_exists('include', $config) and !empty($include = $config['include'])) {
            if (
                !($includeFile = realpath(dirname($include).'/'.basename($include))) or
                !is_readable($includeFile) or !is_file($includeFile) or
                substr($includeFile, -4) !== '.php'
            ) {
                throw new \InvalidArgumentException('The include file "'.$include.'" is not a readable php file.');
            }

            try {
                require_once $includeFile;
            } catch (\Exception $e) {
                throw new \RuntimeException('The include file "'.$include.'" threw an exception: "'.$e->getMessage().'" on line '.$e->getLine());
            }
        }

        // This outputs all the events that are fired, useful for learning
        // about when events are fired in the command flow
        if (array_key_exists('events', $config) and $config['events'] === true) {
            Event::listen('*', function ($event) use ($output): void {
                $data = array_map(
                    function ($d) {
                        /** @var mixed $d */
                        $d instanceof \Exception and ($d = '"'.$d->getMessage().'"');
                        is_array($d) and ($d = '['.implode(',', $d).']');

                        return (string)$d;
                    },
                    array_slice(func_get_args(), 1)
                );

                $output->writeln('<comment>-> event:'.Event::eventName($event).'('.implode(',', $data).')</comment>');
            });
        }
    }

    /**
     * Should the console output be of the polling format
     *
     * @return bool
     */
    public function pollingConsoleOutput(): bool
    {
        return false;
    }

    /**
     * Helper function that passes through to logger instance
     *
     * @see Logger::log for more information
     */
    public function log()
    {
        return call_user_func_array([$this->logger, 'log'], func_get_args());
    }

    /**
     * Parses the configuration file
     *
     * @param array $config
     * @param array $defaults
     *
     * @return bool
     */
    protected function parseConfig(array $config, array $defaults): bool
    {
        if (array_key_exists('config', $config)) {
            $configFileData = Config::parseConfig($config['config']);

            foreach ($config as $key => &$value) {
                // If the config value is equal to the default value set in the command then
                // have a look at the config file. This is so that the config options can be
                // over-ridden in the command line.
                if (
                    isset($this->configOptionMap[$key]) and
                    (
                        ($key === 'verbose' or $value === $defaults[$key]) and
                        (false !== Util::path($configFileData, $this->configOptionMap[$key], $found))
                    )
                ) {
                    switch ($key) {
                        // Need to make sure the log handlers are in the correct format
                        case 'log':
                            $value = [];
                            foreach ((array)$found as $handler => $target) {
                                $handler = strtolower($handler);

                                if ($target !== true) {
                                    $handler .= ':';

                                    if (in_array($handler, ['redis:', 'mongodb:', 'couchdb:', 'amqp:'])) {
                                        $handler .= '//';
                                    }

                                    $handler .= $target;
                                }

                                $value[] = $handler;
                            }

                            break;
                        default:
                            $value = $found;
                            break;
                    }
                }
            }

            $this->config = $config;

            return true;
        }

        return false;
    }

    /**
     * Returns all config items or a specific one
     *
     * @param  string|null  $key
     * @return array|string
     */
    protected function getConfig(?string $key = null)
    {
        if (!is_null($key)) {
            if (!array_key_exists($key, $this->config)) {
                throw new \InvalidArgumentException('Config key "'.$key.'" does not exist. Valid keys are: "'.implode(', ', array_keys($this->config)).'"');
            }

            return $this->config[$key];
        }

        return $this->config;
    }
}
