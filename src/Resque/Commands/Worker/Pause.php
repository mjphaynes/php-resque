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
 * Worker pause command class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Pause extends Command
{
    protected function configure()
    {
        $this->setName('worker:pause')
            ->setDefinition($this->mergeDefinitions(array(
                new InputArgument('id', InputArgument::OPTIONAL, 'The id of the worker to pause (optional; if not present pauses all workers).'),
            )))
            ->setDescription('Pause a running worker. If no worker id set then pauses all workers')
            ->setHelp('Pause a running worker. If no worker id set then pauses all workers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');

        // Do a cleanup
        $worker = new Resque\Worker('*');
        $worker->cleanup();

        if ($id) {
            if (false === ($worker = Resque\Worker::hostWorker($id))) {
                $this->log('There is no worker with id "'.$id.'".', Resque\Logger::ERROR);
                return;
            }

            $workers = array($worker);
        } else {
            $workers = Resque\Worker::hostWorkers();
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
    }
}
