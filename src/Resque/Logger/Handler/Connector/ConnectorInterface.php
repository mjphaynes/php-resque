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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Monolog connector interface class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
interface ConnectorInterface
{

    /**
     * Resolves the handler class
     *
     * @param  Command          $command
     * @param  InputInterface   $input
     * @param  OutputInterface  $output
     * @param  array            $args
     * @return HandlerInterface
     */
    public function resolve(Command $command, InputInterface $input, OutputInterface $output, array $args);

    /**
     * Returns the processor for this handler
     *
     * @param  Command         $command
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  array           $args
     * @return callable
     */
    public function processor(Command $command, InputInterface $input, OutputInterface $output, array $args);

    /**
     * Replaces all instances of [%host%, %worker%, %pid%, %date%, %time%]
     * in logger target key so can be unique log per worker
     *
     * @param  string $string Input string
     * @return string
     */
    public function replacePlaceholders($string);
}
