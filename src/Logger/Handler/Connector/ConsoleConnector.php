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

use Resque\Logger\Handler\ConsoleHandler;
use Resque\Logger\Processor\ConsoleProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console monolog connector class
 *
 * @package Resque
 * @author Michael Haynes
 */
class ConsoleConnector extends AbstractConnector
{
    public function resolve(Command $command, InputInterface $input, OutputInterface $output, array $args): ConsoleHandler
    {
        return new ConsoleHandler($output);
    }

    public function processor(Command $command, InputInterface $input, OutputInterface $output, array $args): ConsoleProcessor
    {
        return new ConsoleProcessor($command, $input, $output);
    }
}
