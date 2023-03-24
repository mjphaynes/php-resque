<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Logger\Handler\Connector;

use Monolog\Handler\RedisHandler;
use Resque\Config;
use Resque\Redis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Redis monolog connector class
 *
 * @package Resque
 * @author Michael Haynes
 */
class RedisConnector extends AbstractConnector
{
    public function resolve(Command $command, InputInterface $input, OutputInterface $output, array $args): RedisHandler
    {
        $options = [
            'scheme' => 'tcp',
            'host'   => $args['host'],
            'port'   => $args['port'],
        ];

        $password = Config::read('redis.password', Redis::DEFAULT_PASSWORD);
        if ($password !== null && $password !== false && trim($password) !== '') {
            $options['password'] = $password;
        }

        $redis = new \Predis\Client($options);

        $namespace = Config::read('redis.namespace', Redis::DEFAULT_NS);
        if (substr($namespace, -1) !== ':') {
            $namespace .= ':';
        }

        $key = $this->replacePlaceholders($args['key']);
        if (strpos($key, $namespace) !== 0) {
            $key = $namespace.$key;
        }

        return new RedisHandler($redis, $key);
    }
}
