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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Queues command class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class Queues extends Command
{
    protected function configure(): void
    {
        $this->setName('queues')
            ->setDefinition($this->mergeDefinitions([]))
            ->setDescription('Get queue statistics')
            ->setHelp('Get queue statistics');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queues = Resque\Redis::instance()->smembers('queues');

        if (empty($queues)) {
            $this->log('<warn>There are no queues.</warn>');
            return self::FAILURE;
        }

        $table = new Resque\Helpers\Table($this);
        $table->setHeaders(['#', 'Name', 'Queued', 'Delayed', 'Processed', 'Failed', 'Cancelled', 'Total']);

        foreach ($queues as $i => $queue) {
            $stats = Resque\Redis::instance()->hgetall(Resque\Queue::redisKey($queue, 'stats'));

            $table->addRow([
                $i + 1, $queue,
                (int)@$stats['queued'],
                (int)@$stats['delayed'],
                (int)@$stats['processed'],
                (int)@$stats['failed'],
                (int)@$stats['cancelled'],
                (int)@$stats['total']
            ]);
        }

        $this->log((string)$table);

        return self::SUCCESS;
    }
}
