<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Logger\Processor;

use Resque\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Process output for console display
 *
 * @package Resque
 * @author Michael Haynes
 */
class ConsoleProcessor
{
    /**
     * @var Command command instance
     */
    protected Command $command;

    /**
     * @var InputInterface input instance
     */
    protected InputInterface $input;

    /**
     * @var OutputInterface output instance
     */
    protected OutputInterface $output;

    /**
     * Create a new instance
     */
    public function __construct(Command $command, InputInterface $input, OutputInterface $output)
    {
        $this->command = $command;
        $this->input   = $input;
        $this->output  = $output;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record): array
    {
        if ($this->command->pollingConsoleOutput()) {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $record['message'] = sprintf('** [%s] %s', date('H:i:s Y-m-d'), $record['message']);
            } else {
                $record['message'] = sprintf('** %s', $record['message']);
            }
        }

        return $record;
    }
}
