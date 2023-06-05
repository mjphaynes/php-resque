<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Socket;

use Resque\Logger;

/**
 * Socket server management
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Server
{
    /**
     * Default IP to use
     */
    public const DEFAULT_IP = '0.0.0.0';

    /**
     * Which port to use
     */
    public const DEFAULT_PORT = 7370;

    /**
     * Which protocol to use
     */
    public const PROTOCOL = 'tcp';

    /**
     * Client connect event
     */
    public const CLIENT_CONNECT = 1;

    /**
     * Client receive message event
     */
    public const CLIENT_RECEIVE = 2;

    /**
     * Client disconnect event
     */
    public const CLIENT_DISCONNECT = 3;

    /**
     * @var array Configuration information used by the server.
     */
    protected $config = [];

    /**
     * @var Logger Monolog logger interface
     */
    protected $logger;

    /**
     * @var array Dictionary of events and the callbacks attached to them.
     */
    protected $events = [];

    /**
     * @var \Socket The socket used by the server.
     */
    protected $socket;

    /**
     * @var int The maximum number of clients allowed to connect.
     */
    protected $max_clients = 10;

    /**
     * @var int The maximum number of bytes to read from a socket at a single time.
     */
    protected $max_read = 1024;

    /**
     * @var int Connection timeout
     */
    protected $tv_sec = 5;

    /**
     * @var bool if the server has started
     */
    protected $started = false;

    /**
     * @var bool True if on the next iteration, the server should shutdown.
     */
    protected $shutdown = false;

    /**
     * @var array The connected clients.
     */
    protected $clients = [];

    /**
     * Creates the socket and starts listening to it.
     *
     * @param array  $config Array of configuration options
     * @param Logger $logger Output logger
     */
    public function __construct(array $config, Logger $logger)
    {
        $this->logger = $logger;

        $defaults = [
            'ip'       => self::DEFAULT_IP,
            'port'     => self::DEFAULT_PORT,
            'protocol' => self::PROTOCOL,
        ];

        $this->config = array_merge($defaults, $config);
    }

    public function __toString()
    {
        return $this->config['ip'].':'.$this->config['port'];
    }

    /**
     * Starts the server
     */
    public function start(): void
    {
        if (false === ($this->socket = @socket_create(AF_INET, SOCK_STREAM, getprotobyname($this->config['protocol'])))) {
            throw new SocketException(sprintf(
                'socket_create(AF_INET, SOCK_STREAM, <%s>) failed: [%d] %s',
                $this->config['protocol'],
                $code = socket_last_error(),
                socket_strerror($code)
            ));
        }

        if (false === @socket_bind($this->socket, $this->config['ip'], $this->config['port'])) {
            throw new SocketException(sprintf(
                'socket_bind($socket, "%s", %d) failed: [%d] %s',
                $this->config['ip'],
                $this->config['port'],
                $code = socket_last_error(),
                socket_strerror($code)
            ));
        }

        if (false === @socket_getsockname($this->socket, $this->config['ip'], $this->config['port'])) {
            throw new SocketException(sprintf(
                'socket_getsockname($socket, "%s", %d) failed: [%d] %s',
                $this->config['ip'],
                $this->config['port'],
                $code = socket_last_error(),
                socket_strerror($code)
            ));
        }

        if (false === @socket_listen($this->socket)) {
            throw new SocketException(sprintf('socket_listen($socket) failed: [%d] %s', $code = socket_last_error(), socket_strerror($code)));
        }

        $this->started = true;

        $this->log('Listening for connections on <pop>'.$this.'</pop>', Logger::INFO);
    }

    /**
     * Schedule a shutdown. Will finish processing the current run.
     */
    public function shutdown(): void
    {
        $this->shutdown = true;
    }

    /**
     * Closes the socket on shutdown
     */
    public function close(): void
    {
        foreach ($this->clients as &$client) {
            $this->disconnect($client, 'Receiver shutting down... Goodbye.');
        }

        socket_close($this->socket);
    }

    /**
     * Runs the server code until the server is shut down.
     */
    public function run(): void
    {
        if (!$this->started) {
            $this->start();
        }

        if (function_exists('pcntl_signal')) {
            // PHP 7.1 allows async signals
            if (function_exists('pcntl_async_signals')) {
                pcntl_async_signals(true);
            } else {
                declare(ticks=1);
            }
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGQUIT, [$this, 'shutdown']);
        }

        register_shutdown_function([$this, 'close']);

        while (true) {
            if ($this->shutdown) {
                $this->log('Shutting down listener on <pop>'.$this.'</pop>', Logger::INFO);
                break;
            }

            $read = [$this->socket];
            foreach ($this->clients as &$client) {
                $read[] = $client->getSocket();
            }

            // Set up a blocking call to socket_select
            $write = $except = null;
            if ((@socket_select($read, $write, $except, $this->tv_sec)) < 1) {
                // $this->log('Waiting for socket update', self::LOG_VERBOSE);
                continue;
            }

            // Handle new Connections
            if (in_array($this->socket, $read)) {
                if (count($this->clients) >= $this->max_clients) {
                    $this->log('New client trying to connect but maximum <pop>'.$this->max_clients.'</pop> clients already connected', Logger::INFO);

                    $client = new Client($this->socket);
                    $this->send($client, '{"ok":0,"message":"Could not connect, hit max number of connections"}');
                    $client->disconnect();
                    continue;
                } else {
                    $this->clients[] = $client = new Client($this->socket);

                    $this->log('New client connected: <pop>'.$client.'</pop>', Logger::INFO);

                    $this->fire(self::CLIENT_CONNECT, $client);
                }
            }

            // Handle input for each client
            foreach ($this->clients as $i => $client) {
                if (in_array($client->getSocket(), $read)) {
                    $data = @socket_read($client->getSocket(), $this->max_read);

                    if ($data === false) {
                        $this->disconnect($client);
                        continue;
                    }

                    // Remove any control characters
                    $data = preg_replace('/[\x00-\x1F\x7F]/', '', trim($data));

                    if (empty($data)) {
                        // Send a null byte to flush
                        $this->send($client, "\0");
                    } else {
                        $this->log(sprintf('Received "<comment>%s</comment>" from <pop>%s</pop>', $data, $client), Logger::INFO);
                        $this->fire(self::CLIENT_RECEIVE, $client, $data);
                    }
                }
            }
        }
    }

    /**
     * Writes data to the socket, including the length of the data, and ends it with a CRLF unless specified.
     * It is perfectly valid for socket_write to return zero which means no bytes have been written.
     * Be sure to use the === operator to check for FALSE in case of an error.
     *
     * @param  Client   $client  Connected client to write to
     * @param  string   $message Data to write to the socket.
     * @param  bool     $end     Whether to end the message with a newline
     * @return int|bool Returns the number of bytes successfully written to the socket or FALSE on failure.
     *                          The error code can be retrieved with socket_last_error(). This code may be passed to
     *                          socket_strerror() to get a textual explanation of the error.
     */
    public function send(Client &$client, string $message, bool $end = true)
    {
        $this->log('Messaging client <pop>'.$client.'</pop> with "<comment>'.str_replace("\n", '\n', $message).'</comment>"');

        $end and $message = "$message\n";
        $length = strlen($message);
        $sent = 0;

        while (true) {
            $attempt = @socket_write($client->getSocket(), $message, $length);

            if ($attempt === false) {
                return false;
            }

            $sent += $attempt;

            if ($attempt < $length) {
                $message = substr($message, $attempt);
                $length -= $attempt;
            } else {
                return $attempt;
            }
        }

        return false;
    }

    /**
     * Disconnect a client
     *
     * @param Client $client  The client to disconnect
     * @param string $message Data to write to the socket.
     */
    public function disconnect(Client $client, string $message = 'Goodbye.'): void
    {
        $this->fire(self::CLIENT_DISCONNECT, $client, $message);

        $this->log('Client disconnected: <pop>'.$client.'</pop>', Logger::INFO);

        $client->disconnect();

        $i = array_search($client, $this->clients);
        unset($this->clients[$i]);
    }

    /**
     * Helper function to make using connect event easier
     *
     * @param callable $callback Any callback callable by call_user_func_array.
     *
     * @return bool
     */
    public function onConnect(callable $callback): bool
    {
        return $this->listen(self::CLIENT_CONNECT, $callback);
    }

    /**
     * Helper function to make using receive event easier
     *
     * @param callable $callback Any callback callable by call_user_func_array.
     *
     * @return bool
     */
    public function onReceive(callable $callback): bool
    {
        return $this->listen(self::CLIENT_RECEIVE, $callback);
    }

    /**
     * Helper function to make using disconnect event easier
     *
     * @param callable $callback Any callback callable by call_user_func_array.
     *
     * @return bool
     */
    public function onDisconnect(callable $callback): bool
    {
        return $this->listen(self::CLIENT_DISCONNECT, $callback);
    }

    /**
     * Adds a function to be called whenever a certain action happens
     *
     * @param string $event    Name of event to listen on.
     * @param mixed  $callback Any callback callable by call_user_func_array.
     *
     * @return true
     */
    public function listen(string $event, callable $callback): bool
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        $this->events[$event][] = $callback;

        return true;
    }

    /**
     * Deletes a function from the call list for a certain action
     *
     * @param string $event    Name of event.
     * @param mixed  $callback The callback as defined when listen() was called.
     *
     * @return true
     */
    public function forget(string $event, callable $callback): bool
    {
        if (!isset($this->events[$event])) {
            return true;
        }

        $key = array_search($callback, $this->events[$event]);

        if ($key !== false) {
            unset($this->events[$event][$key]);
        }

        return true;
    }

    /**
     * Raise a given event with the supplied data.
     *
     * @param string $event  Name of event to be raised.
     * @param Client $client Connected client
     * @param mixed  $data   Optional, any data that should be passed to each callback.
     *
     * @return true
     */
    public function fire(string $event, Client &$client, $data = null): bool
    {
        $retval = true;

        if (!array_key_exists($event, $this->events)) {
            return false;
        }

        foreach ($this->events[$event] as $callback) {
            if (!is_callable($callback)) {
                continue;
            }

            if (($retval = call_user_func($callback, $this, $client, $data)) === false) {
                break;
            }
        }

        return $retval !== false;
    }

    /**
     * Send log message to logger
     */
    public function log()
    {
        return call_user_func_array([$this->logger, 'log'], func_get_args());
    }

    /**
     * Write a string to a resource
     *
     * @param resource $fh     The resource to write to
     * @param string   $string The string to write
     */
    public static function fwrite($fh, string $string)
    {
        $fwrite = 0;

        for ($written = 0; $written < strlen($string); $written += $fwrite) {
            if (($fwrite = fwrite($fh, substr($string, $written))) === false) {
                return $written;
            }
        }

        return $written;
    }
}
