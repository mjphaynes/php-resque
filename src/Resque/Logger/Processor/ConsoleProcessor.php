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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Process output for console display
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class ConsoleProcessor
{

    /**
     * @var Command command instance
     */
    protected $command;

    /**
     * @var InputInterface input instance
     */
    protected $input;

    /**
     * @var OutputInterface output instance
     */
    protected $output;

    /**
     * Creates a new instance
     * @return void
     */
    public function __construct(Command $command, InputInterface $input, OutputInterface $output)
    {
        $this->command = $command;
        $this->input   = $input;
        $this->output  = $output;
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        if ($this->command->pollingConsoleOutput()) {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $record['message'] = sprintf('** [%s] %s', strftime('%T %Y-%m-%d'), $record['message']);
            } else {
                $record['message'] = sprintf('** %s', $record['message']);
            }
        }

        return $record;
    }
}
