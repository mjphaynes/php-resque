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
 * Worker restart command class
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class Restart extends Command
{
    protected function configure(): void
    {
        $this->setName('worker:restart')
            ->setDefinition($this->mergeDefinitions([
                new InputArgument('id', InputArgument::OPTIONAL, 'The id of the worker to restart (optional; if not present restarts all workers).'),
            ]))
            ->setDescription('Restart a running worker. If no worker id set then restarts all workers')
            ->setHelp('Restart a running worker. If no worker id set then restarts all workers');
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
            if (posix_kill($worker->getPid(), SIGTERM)) {
                $child = pcntl_fork();

                // Failed
                if ($child == -1) {
                    $this->log('Unable to fork, worker '.$worker.' has been stopped.', \Resque\Logger::CRITICAL);

                // Parent
                } elseif ($child > 0) {
                    $this->log('Worker <pop>'.$worker.'</pop> restarted.');
                    continue;

                // Child
                } else {
                    $new_worker = new Worker($worker->getQueues(), $worker->getBlocking());
                    $new_worker->setInterval($worker->getInterval());
                    $new_worker->setTimeout($worker->getTimeout());
                    $new_worker->setMemoryLimit($worker->getMemoryLimit());
                    $new_worker->setLogger($this->logger);
                    $new_worker->work();

                    $this->log('Worker <pop>'.$worker.'</pop> restarted as <pop>'.$new_worker.'</pop>.');
                }
            } else {
                $this->log('Worker <pop>'.$worker.'</pop> <error>could not send TERM signal.</error>');
            }
        }

        return Command::SUCCESS;
    }
}
