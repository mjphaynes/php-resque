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
 * Hosts command
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Hosts extends Command
{
    protected function configure()
    {
        $this->setName('hosts')
            ->setDefinition($this->mergeDefinitions(array(
            )))
            ->setDescription('List hosts with running workers')
            ->setHelp('List hosts with running workers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hosts = Resque\Redis::instance()->smembers(Resque\Host::redisKey());

        if (empty($hosts)) {
            $this->log('<warn>There are no hosts with running workers.</warn>');
            return;
        }

        $table = new Resque\Helpers\Table($this);
        $table->setHeaders(array('#', 'Hostname', '# workers'));

        foreach ($hosts as $i => $hostname) {
            $host = new Resque\Host($hostname);
            $workers = Resque\Redis::instance()->scard(Resque\Host::redisKey($host));

            $table->addRow(array($i + 1, $hostname, $workers));
        }

        $this->log((string)$table);
    }
}
