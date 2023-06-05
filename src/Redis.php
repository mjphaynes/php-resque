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

use Predis\Client;

/**
 * Resque redis class
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Redis
{
    /**
     * Default Redis connection scheme
     */
    public const DEFAULT_SCHEME = 'tcp';

    /**
     * Default Redis connection host
     */
    public const DEFAULT_HOST = '127.0.0.1';

    /**
     * Default Redis connection port
     */
    public const DEFAULT_PORT = 6379;

    /**
     * Default Redis namespace
     */
    public const DEFAULT_NS = 'resque';

    /**
     * Default Redis AUTH password
     */
    public const DEFAULT_PASSWORD = null;

    /**
     * Default Redis Read Write Timeout
     */
    public const DEFAULT_RW_TIMEOUT = 60;

    /**
     * Default Redis option for using phpiredis or not
     * @deprecated Since 4.0.0, ext-phpiredis is abandoned and will be removed in the future
     */
    public const DEFAULT_PHPIREDIS = false;

    /**
     * @var array Default configuration
     */
    protected static $config = [
        'scheme'     => self::DEFAULT_SCHEME,
        'host'       => self::DEFAULT_HOST,
        'port'       => self::DEFAULT_PORT,
        'namespace'  => self::DEFAULT_NS,
        'password'   => self::DEFAULT_PASSWORD,
        'rw_timeout' => self::DEFAULT_RW_TIMEOUT,
        'phpiredis'  => self::DEFAULT_PHPIREDIS,
    ];

    /**
     * @var Redis Redis instance
     */
    protected static $instance = null;

    /**
     * @var Client The Predis instance
     */
    protected $redis;

    /**
     * @var string Redis namespace
     */
    protected $namespace;

    /**
     * @var array List of all commands in Redis that supply a key as their
     *            first argument. Used to prefix keys with the Resque namespace.
     */
    protected $keyCommands = [
        'exists',
        'del',
        'type',
        'keys',
        'expire',
        'ttl',
        'move',
        'set',
        'setex',
        'get',
        'getset',
        'setnx',
        'incr',
        'incrby',
        'decr',
        'decrby',
        'rpush',
        'lpush',
        'llen',
        'lrange',
        'ltrim',
        'lindex',
        'lset',
        'lrem',
        'lpop',
        'blpop',
        'rpop',
        'sadd',
        'srem',
        'spop',
        'scard',
        'sismember',
        'smembers',
        'srandmember',
        'hdel',
        'hexists',
        'hget',
        'hgetall',
        'hincrby',
        'hincrbyfloat',
        'hkeys',
        'hlen',
        'hmget',
        'hmset',
        'hset',
        'hsetnx',
        'hvals',
        'zadd',
        'zrem',
        'zrange',
        'zrevrange',
        'zrangebyscore',
        'zrevrangebyscore',
        'zcard',
        'zscore',
        'zremrangebyscore',
        'sort',
        // sinterstore
        // sunion
        // sunionstore
        // sdiff
        // sdiffstore
        // sinter
        // smove
        // rename
        // rpoplpush
        // mget
        // msetnx
        // mset
        // renamenx
    ];

    /**
     * Establish a Redis connection.
     *
     * @param array $config Array of configuration settings
     */
    final public function __construct(array $config = [])
    {
        $predisParams  = [];
        $predisOptions = [];
        if (!empty($config['predis'])) {
            $predisParams  = $config['predis']['config'];
            $predisOptions = $config['predis']['options'];
        } else {
            foreach (['scheme', 'host', 'port'] as $key) {
                if (!isset($config[$key])) {
                    throw new \InvalidArgumentException("key '{$key}' is missing in redis configuration");
                }
            }

            // non-optional configuration parameters
            $predisParams = [
                'scheme' => $config['scheme'],
                'host'   => $config['host'],
                'port'   => $config['port'],
            ];

            // setup password
            if (!empty($config['password'])) {
                $predisParams['password'] = $config['password'];
            }

            // setup read/write timeout
            if (!empty($config['rw_timeout'])) {
                $predisParams['read_write_timeout'] = $config['rw_timeout'];
            }

            // setup predis client options
            if (!empty($config['phpiredis'])) {
                $predisOptions = [
                    'connections' => [
                        'tcp'  => 'Predis\Connection\PhpiredisStreamConnection',
                        'unix' => 'Predis\Connection\PhpiredisSocketConnection',
                    ],
                ];
            }
        }

        // create Predis client
        $this->redis = $this->initializePredisClient($predisParams, $predisOptions);

        // setup namespace
        if (!empty($config['namespace'])) {
            $this->setNamespace($config['namespace']);
        } else {
            $this->setNamespace(self::DEFAULT_NS);
        }

        // Do this to test connection is working now rather than later
        $this->redis->connect();
    }

    /**
     * Establish a Redis connection
     *
     * @return Redis
     */
    public static function instance(): Redis
    {
        if (!static::$instance) {
            static::$instance = new static(static::$config);
        }

        return static::$instance;
    }

    /**
     * Set the Redis config
     *
     * @param array $config Array of configuration settings
     */
    public static function setConfig(array $config): void
    {
        static::$config = array_merge(static::$config, $config);
    }

    /**
     * initialize the redis member with a predis client.
     * isolated call for testability
     * @param array $config  predis config parameters
     * @param array $options predis optional parameters
     *
     * @return Client
     */
    public function initializePredisClient(array $config, array $options): Client
    {
        return new Client($config, $options);
    }

    /**
     * Set Redis namespace
     *
     * @param string $namespace New namespace
     */
    public function setNamespace(string $namespace): void
    {
        if (substr($namespace, -1) !== ':') {
            $namespace .= ':';
        }

        $this->namespace = $namespace;
    }

    /**
     * Get Redis namespace
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Add Redis namespace to a string
     *
     * @param  array|string $string String to namespace
     * @return array|string
     */
    public function addNamespace($string)
    {
        if (is_array($string)) {
            foreach ($string as &$str) {
                $str = $this->addNamespace($str);
            }

            return $string;
        }

        if (strpos($string, $this->namespace) !== 0) {
            $string = $this->namespace . $string;
        }

        return $string;
    }

    /**
     * Remove Redis namespace from string
     *
     * @param  string $string String to de-namespace
     * @return string
     */
    public function removeNamespace(string $string): string
    {
        $prefix = $this->namespace;

        if (substr($string, 0, strlen($prefix)) == $prefix) {
            $string = substr($string, strlen($prefix), strlen($string));
        }

        return $string;
    }

    /**
     * Dynamically pass calls to the Predis.
     *
     * @param  string $method     Method to call
     * @param  array  $parameters Arguments to send to method
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if (in_array($method, $this->keyCommands)) {
            $parameters[0] = $this->addNamespace($parameters[0]);
        }

        return call_user_func_array([$this->redis, $method], $parameters);
    }
}
