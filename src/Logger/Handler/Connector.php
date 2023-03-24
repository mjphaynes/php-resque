<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Logger\Handler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Monolog connector class
 *
 * @package Resque
 * @author Michael Haynes
 */
class Connector
{
    /**
     * @var Command command instance
     */
    protected $command;

    /**
     * @var InputInterface input instance
     */
    protected $input;

    /**
     * @var OutputInterface output instance
     */
    protected $output;

    /**
     * @var array output instance
     */
    private $connectionMap = [
        'Redis'    => 'redis://(?P<host>[a-z0-9\._-]+):(?P<port>\d+)/(?P<key>.+)',               // redis://127.0.0.1:6379/log:%worker$
        'MongoDB'  => 'mongodb://(?P<host>[a-z0-9\._-]+):(?P<port>\d+)/(?P<dbname>[a-z0-9_]+)/(?P<collection>.+)',  // mongodb://127.0.0.1:27017/dbname/log:%worker%
        'CouchDB'  => 'couchdb://(?P<host>[a-z0-9\._-]+):(?P<port>\d+)/(?P<dbname>[a-z0-9_]+)',  // couchdb://127.0.0.1:27017/dbname
        'Amqp'     => 'amqp://(?P<host>[a-z0-9\._-]+):(?P<port>\d+)/(?P<name>[a-z0-9_]+)',       // amqp://127.0.0.1:5763/name
        'Socket'   => 'socket:(?P<connection>.+)',                           // socket:udp://127.0.0.1:80
        'Syslog'   => 'syslog:(?P<ident>[a-z]+)/(?P<facility>.+)',           // syslog:myfacility/local6
        'ErrorLog' => 'errorlog:(?P<type>\d)',                               // errorlog:0
        'Cube'     => 'cube:(?P<url>.+)',                                    // cube:udp://localhost:5000
        'Rotate'   => 'rotate:(?P<max_files>\d+):(?P<file>.+)',              // rotate:5:path/to/output.log
        'Console'  => '(console|echo)(?P<ignore>\b)',                        // console
        'Off'      => '(off|null)(?P<ignore>\b)',                            // off
        'Stream'   => '(?:stream:)?(?P<stream>[a-z0-9/\\\[\]\(\)\~%\._-]+)',   // stream:path/to/output.log | path/to/output.log
    ];

    /**
     * Create a new Connector instance
     */
    public function __construct(Command $command, InputInterface $input, OutputInterface $output)
    {
        $this->command = $command;
        $this->input   = $input;
        $this->output  = $output;
    }

    /**
     * Resolve a Monolog handler from string input
     *
     * @param  string                   $logFormat
     * @throws InvalidArgumentException
     *
     * @return \Monolog\Handler\HandlerInterface
     */
    public function resolve(string $logFormat): \Monolog\Handler\HandlerInterface
    {
        // Loop over connectionMap and see if the log format matches any of them
        foreach ($this->connectionMap as $connection => $match) {
            // Because the last connection stream is an effective catch all i.e. just specifying a
            // path to a file, lets make sure the user wasn't trying to use another handler but got
            // the format wrong. If they did then show them the correct format
            if ($connection == 'Stream' and stripos($logFormat, 'stream') !== 0) {
                $pattern = '~^(?P<handler>'.implode('|', array_keys($this->connectionMap)).')(.*)$~i';

                if ($possible = $this->matches($pattern, $logFormat)) {
                    // Map to correct key case
                    $handler = str_replace(
                        array_map('strtolower', array_keys($this->connectionMap)),
                        array_keys($this->connectionMap),
                        strtolower($possible['handler'])
                    );

                    // Tell them the error of their ways
                    $format = str_replace(['(?:', ')?', '\)'], '', $this->connectionMap[$handler]);

                    $format = preg_replace_callback('/\(\?P<([a-z_]+)>(?:.+?)\)/', function ($m) {
                        return ($m[1] == 'ignore') ? '' : '<'.$m[1].'>';
                    }, $format);

                    throw new \InvalidArgumentException('Invalid format "'.$logFormat.'" for "'.$handler.'" handler. Should be of format "'.$format.'"');
                }
            }

            if ($args = $this->matches('~^'.$match.'$~i', $logFormat)) {
                $connectorClass = new \ReflectionClass('Resque\Logger\Handler\Connector\\'.$connection.'Connector');
                /** @var ConnectorInterface */
                $connectorClass = $connectorClass->newInstance();

                $handler = $connectorClass->resolve($this->command, $this->input, $this->output, $args);
                $handler->pushProcessor($connectorClass->processor($this->command, $this->input, $this->output, $args));

                return $handler;
            }
        }

        throw new \InvalidArgumentException('Log format "'.$logFormat.'" is invalid');
    }

    /**
     * Performs a pattern match on a string and returns just
     * the named matches or false if no match
     *
     * @param string $pattern
     * @param string $subject
     *
     * @return array|false
     */
    private function matches(string $pattern, string $subject)
    {
        if (preg_match($pattern, $subject, $matches)) {
            $args = [];

            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $args[$key] = $value;
                }
            }

            return $args;
        }

        return false;
    }
}
