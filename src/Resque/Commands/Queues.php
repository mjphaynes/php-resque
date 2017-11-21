<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Commands;

use Resque;
use Resque\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Queues command class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Queues extends Command
{
    protected function configure()
    {
        $this->setName('queues')
            ->setDefinition($this->mergeDefinitions(array(
            )))
            ->setDescription('Get queue statistics')
            ->setHelp('Get queue statistics')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queues = Resque\Redis::instance()->smembers('queues');

        if (empty($queues)) {
            $this->log('<warn>There are no queues.</warn>');
            return;
        }

        $table = new Resque\Helpers\Table($this);
        $table->setHeaders(array('#', 'Name', 'Queued', 'Delayed', 'Processed', 'Failed', 'Cancelled', 'Total'));

        foreach ($queues as $i => $queue) {
            $stats = Resque\Redis::instance()->hgetall(Resque\Queue::redisKey($queue, 'stats'));

            $table->addRow(array(
                $i + 1, $queue,
                (int)@$stats['queued'],
                (int)@$stats['delayed'],
                (int)@$stats['processed'],
                (int)@$stats['failed'],
                (int)@$stats['cancelled'],
                (int)@$stats['total']
            ));
        }

        $this->log((string)$table);
    }
}
