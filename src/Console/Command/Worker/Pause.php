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
 * Worker pause command class
 *
 * @package Resque
 * @author Michael Haynes
 */
final class Pause extends Command
{
    protected function configure(): void
    {
        $this->setName('worker:pause')
            ->setDefinition($this->mergeDefinitions([
                new InputArgument('id', InputArgument::OPTIONAL, 'The id of the worker to pause (optional; if not present pauses all workers).'),
            ]))
            ->setDescription('Pause a running worker. If no worker id set then pauses all workers')
            ->setHelp('Pause a running worker. If no worker id set then pauses all workers');
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
                return self::FAILURE;
            }

            $workers = [$worker];
        } else {
            $workers = Worker::hostWorkers();
        }

        if (!count($workers)) {
            $this->log('<warn>There are no workers on this host.<warn>');
        }

        foreach ($workers as $worker) {
            if (posix_kill($worker->getPid(), SIGUSR2)) {
                $this->log('Worker <pop>'.$worker.'</pop> USR2 signal sent.');
            } else {
                $this->log('Worker <pop>'.$worker.'</pop> <error>could not send USR2 signal.</error>');
            }
        }

        return self::SUCCESS;
    }
}
