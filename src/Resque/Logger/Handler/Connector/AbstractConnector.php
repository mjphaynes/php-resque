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

use Resque;
use Resque\Logger\Processor\StripFormatProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract monolog connector class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
abstract class AbstractConnector implements ConnectorInterface
{

    /**
     * The default processor is the StripFormatProcessor which
     * removes all the console colour formatting from the string
     *
     * @param  Command         $command
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  array           $args
     * @return StripProcessor
     */
    public function processor(Command $command, InputInterface $input, OutputInterface $output, array $args)
    {
        return new StripFormatProcessor($command, $input, $output);
    }

    /**
     * Replaces all instances of [%host%, %worker%, %pid%, %date%, %time%]
     * in logger target key so can be unique log per worker
     *
     * @param  string $string Input string
     * @return string
     */
    public function replacePlaceholders($string)
    {
        $placeholders = array(
            '%host%'   => new Resque\Host,
            '%worker%' => new Resque\Worker,
            '%pid%'    => getmypid(),
            '%date%'   => date('Y-m-d'),
            '%time%'   => date('H:i')
        );

        return strtr($string, $placeholders);
    }
}
