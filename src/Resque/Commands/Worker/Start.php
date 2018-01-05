<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Commands\Worker;

use Resque;
use Resque\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Worker start command class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Start extends Command
{
    protected function configure()
    {
        $this->setName('worker:start')
            ->setDefinition($this->mergeDefinitions(array(
                new InputOption('queue', 'Q', InputOption::VALUE_OPTIONAL, 'The queue(s) to listen on, comma separated.', '*'),
                new InputOption('blocking', 'b', InputOption::VALUE_OPTIONAL, 'Use Redis pop blocking or time interval.', true),
                new InputOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Blocking timeout/interval speed in seconds.', 10),
                new InputOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Seconds a job may run before timing out.', 60),
                new InputOption('memory', 'm', InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes.', 128),
                new InputOption('pid', 'P', InputOption::VALUE_OPTIONAL, 'Absolute path to PID file, must be writeable by worker.'),
            )))
            ->setDescription('Polls for jobs on specified queues and executes job when found')
            ->setHelp('Polls for jobs on specified queues and executes job when found')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getConfig('queue');
        $blocking = filter_var($this->getConfig('blocking'), FILTER_VALIDATE_BOOLEAN);

        // Create worker instance
        $worker = new Resque\Worker($queue, $blocking);
        $worker->setLogger($this->logger);

        if ($pidFile = $this->getConfig('pid')) {
            $worker->setPidFile($pidFile);
        }

        if ($interval = $this->getConfig('interval')) {
            $worker->setInterval($interval);
        }

        if ($timeout = $this->getConfig('timeout')) {
            $worker->setTimeout($timeout);
        }

        // The memory limit is the amount of memory we will allow the script to occupy
        // before killing it and letting a process manager restart it for us, which
        // is to protect us against any memory leaks that will be in the scripts.
        if ($memory = $this->getConfig('memory')) {
            $worker->setMemoryLimit($memory);
        }

        $worker->work();
    }

    public function pollingConsoleOutput()
    {
        return true;
    }
}
