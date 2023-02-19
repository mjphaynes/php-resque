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

/**
 * Socket client connection
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Client
{
    /**
     * @var \Socket The client's socket resource, for sending and receiving data with
     */
    protected ?\Socket $socket = null;

    /**
     * @var string The client's IP address, as seen by the server
     */
    protected string $ip;

    /**
     * @var int If given, this will hold the port associated to address
     */
    protected int $port;

    /**
     * The client's hostname, as seen by the server. This
     * variable is only set after calling lookup_hostname,
     * as hostname lookups can take up a decent amount of time.
     *
     * @var string
     */
    protected ?string $hostname = null;

    /**
     * Creates the client
     *
     * @param \Socket $socket The resource of the socket the client is connecting by, generally the master socket.
     */
    public function __construct(\Socket &$socket)
    {
        if (false === ($this->socket = @socket_accept($socket))) {
            throw new SocketException(sprintf('socket_accept($socket) failed: [%d] %s', $code = socket_last_error(), socket_strerror($code)));
        }

        socket_getpeername($this->socket, $this->ip, $this->port);
    }

    public function __toString()
    {
        return $this->ip.':'.$this->port;
    }

    /**
     * Closes the socket
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Returns this clients socket
     *
     * @return \Socket
     */
    public function getSocket(): \Socket
    {
        return $this->socket;
    }

    /**
     * Gets the IP hostname
     *
     * @return string
     */
    public function getHostname(): string
    {
        if (is_null($this->hostname)) {
            $this->hostname = gethostbyaddr($this->ip);
        }

        return $this->hostname;
    }
}
