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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clean up hosts and workers from Redis
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class Cleanup extends Command
{
    protected function configure(): void
    {
        $this->setName('cleanup')
            ->setDefinition($this->mergeDefinitions([]))
            ->setDescription('Cleans up php-resque data, removing dead hosts, workers and jobs')
            ->setHelp('Cleans up php-resque data, removing dead hosts, workers and jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = new \Resque\Host();
        $cleaned_hosts = $host->cleanup();

        $worker = new \Resque\Worker('*');
        $cleaned_workers = $worker->cleanup();
        $cleaned_hosts = array_merge_recursive($cleaned_hosts, $host->cleanup());

        $cleaned_jobs = \Resque\Job::cleanup();

        $this->log('Cleaned hosts: <pop>'.json_encode($cleaned_hosts['hosts']).'</pop>');
        $this->log('Cleaned workers: <pop>'.json_encode(array_merge($cleaned_hosts['workers'], $cleaned_workers)).'</pop>');
        $this->log('Cleaned <pop>'.$cleaned_jobs['zombie'].'</pop> zombie job'.($cleaned_jobs['zombie'] == 1 ? '' : 's'));
        $this->log('Cleared <pop>'.$cleaned_jobs['processed'].'</pop> processed job'.($cleaned_jobs['processed'] == 1 ? '' : 's'));

        return Command::SUCCESS;
    }
}
