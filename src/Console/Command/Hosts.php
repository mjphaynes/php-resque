<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Console\Command;

use Resque\Host;
use Resque\Redis;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Hosts command
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class Hosts extends Command
{
    protected function configure(): void
    {
        $this->setName('hosts')
            ->setDefinition($this->mergeDefinitions([]))
            ->setDescription('List hosts with running workers')
            ->setHelp('List hosts with running workers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hosts = Redis::instance()->smembers(Host::redisKey());

        if (empty($hosts)) {
            $this->log('<warn>There are no hosts with running workers.</warn>');
            return self::FAILURE;
        }

        $table = new \Resque\Helpers\Table($this);
        $table->setHeaders(['#', 'Hostname', '# workers']);

        foreach ($hosts as $i => $hostname) {
            $host = new Host($hostname);
            $workers = Redis::instance()->scard(Host::redisKey($host));

            $table->addRow([$i + 1, $hostname, $workers]);
        }

        $this->log((string)$table);

        return self::SUCCESS;
    }
}
