<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Console\Command\Worker;

use Resque\Console\Command\Command;
use Resque\Worker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Worker cancel command class
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class Cancel extends Command
{
    protected function configure(): void
    {
        $this->setName('worker:cancel')
            ->setDefinition($this->mergeDefinitions([
                new InputArgument('id', InputArgument::OPTIONAL, 'The id of the worker to cancel it\'s running job (optional; if not present cancels all workers).'),
            ]))
            ->setDescription('Cancel job on a running worker. If no worker id set then cancels all workers')
            ->setHelp('Cancel job on a running worker. If no worker id set then cancels all workers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');

        // Do a cleanup
        $worker = new Worker('*');
        $worker->cleanup();

        if ($id) {
            if (false === ($worker = Worker::hostWorker($id))) {
                $this->log('There is no worker with id "'.$id.'".', \Resque\Logger::ERROR);
                return Command::FAILURE;
            }

            $workers = [$worker];
        } else {
            $workers = Worker::hostWorkers();
        }

        if (!count($workers)) {
            $this->log('<warn>There are no workers on this host</warn>');
        }

        foreach ($workers as $worker) {
            $packet  = $worker->getPacket();
            $job_pid = (int)$packet['job_pid'];

            if ($job_pid and posix_kill($job_pid, 0)) {
                if (posix_kill($job_pid, SIGUSR1)) {
                    $this->log('Worker <pop>'.$worker.'</pop> running job SIGUSR1 signal sent.');
                } else {
                    $this->log('Worker <pop>'.$worker.'</pop> <error>running job SIGUSR1 signal could not be sent.</error>');
                }
            } else {
                $this->log('Worker <pop>'.$worker.'</pop> has no running job.');
            }
        }

        return Command::SUCCESS;
    }
}
