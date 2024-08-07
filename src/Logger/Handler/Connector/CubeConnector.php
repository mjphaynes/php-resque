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
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 * @deprecated Since 4.0.0, Cube appears abandoned and thus support for its connector will be dropped in the future
 */
class CubeConnector extends AbstractConnector
{
    public function resolve(Command $command, InputInterface $input, OutputInterface $output, array $args): CubeHandler
    {
        return new CubeHandler($this->replacePlaceholders($args['url']));
    }
}
