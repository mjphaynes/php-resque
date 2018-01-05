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

use Monolog\Handler\CubeHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cube monolog connector class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class CubeConnector extends AbstractConnector
{
    public function resolve(Command $command, InputInterface $input, OutputInterface $output, array $args)
    {
        return new CubeHandler($this->replacePlaceholders($args['url']));
    }
}
