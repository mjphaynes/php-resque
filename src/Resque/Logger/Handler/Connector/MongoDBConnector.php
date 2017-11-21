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

use Monolog\Handler\MongoDBHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MongoDB monolog connector class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class MongoDBConnector extends AbstractConnector
{
    public function resolve(Command $command, InputInterface $input, OutputInterface $output, array $args)
    {
        $mongodb = null;
        $dsn     = strtr('mongodb://host:port', $args);
        $options = array();

        if (class_exists('MongoClient')) {
            $mongodb = new \MongoClient($dsn, $options);
        } elseif (class_exists('Mongo')) {
            $mongodb = new \Mongo($dsn, $options);
        }

        return new MongoDBHandler($mongodb, $this->replacePlaceholders($args['dbname']), $this->replacePlaceholders($args['collection']));
    }
}
