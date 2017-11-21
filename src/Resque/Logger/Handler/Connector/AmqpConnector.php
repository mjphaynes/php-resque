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

use Monolog\Handler\AmqpHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Amqp monolog connector class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class AmqpConnector extends AbstractConnector
{
    public function resolve(Command $command, InputInterface $input, OutputInterface $output, array $args)
    {
        $options = array_merge(array(
            'host'     => 'localhost',
            'port'     => 5763,
            'login'    => null,
            'password' => null,
        ), $args);

        $conn = new \AMQPConnection($options);
        $conn->connect();

        $channel = new \AMQPChannel($conn);

        return new AmqpHandler(new \AMQPExchange($channel), $this->replacePlaceholders($args['name']));
    }
}
