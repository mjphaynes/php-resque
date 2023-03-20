<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Command;

use Resque;
use Resque\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Workers command class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class Workers extends Command
{
    protected function configure(): void
    {
        $this->setName('workers')
            ->setAliases(['worker:list'])
            ->setDefinition($this->mergeDefinitions([]))
            ->setDescription('List all running workers on host')
            ->setHelp('List all running workers on host');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workers = Resque\Worker::hostWorkers();

        if (empty($workers)) {
            $this->log('<warn>There are no workers on this host.</warn>');
            return self::FAILURE;
        }

        $table = new Resque\Helpers\Table($this);
        $table->setHeaders(['#', 'Status', 'ID', 'Running for', 'Running job', 'P', 'C', 'F', 'Interval', 'Timeout', 'Memory (Limit)']);

        foreach ($workers as $i => $worker) {
            $packet = $worker->getPacket();

            $table->addRow([
                $i + 1,
                Resque\Worker::$statusText[$packet['status']],
                (string)$worker,
                Resque\Helpers\Util::human_time_diff($packet['started']),
                (!empty($packet['job_id']) ? $packet['job_id'].' for '.Resque\Helpers\Util::human_time_diff($packet['job_started']) : '-'),
                $packet['processed'],
                $packet['cancelled'],
                $packet['failed'],
                $packet['interval'],
                $packet['timeout'],
                Resque\Helpers\Util::bytes($packet['memory']).' ('.$packet['memory_limit'].' MB)',
            ]);
        }

        $this->log((string)$table);

        return self::SUCCESS;
    }
}
