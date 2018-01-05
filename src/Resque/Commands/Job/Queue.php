<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Commands\Job;

use Resque;
use Resque\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Job command class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Queue extends Command
{
    protected function configure()
    {
        $this->setName('job:queue')
            ->setDefinition($this->mergeDefinitions(array(
                new InputArgument('job', InputArgument::REQUIRED, 'The job to run.'),
                new InputArgument('args', InputArgument::OPTIONAL, 'The arguments to send with the job.'),
                new InputOption('queue', 'Q', InputOption::VALUE_OPTIONAL, 'The queue to add the job to.'),
                new InputOption('delay', 'D', InputOption::VALUE_OPTIONAL, 'The amount of time or a unix time to delay execution of job till.'),
            )))
            ->setDescription('Queue a new job to run with optional delay')
            ->setHelp('Queue a new job to run')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $job   = $input->getArgument('job');
        $args  = $input->getArgument('args');
        $queue = $this->getConfig('queue');
        $delay = $this->getConfig('delay');

        if (json_decode($args, true)) {
            $args = (array)json_decode($args, true);
        } else {
            if (is_null($args)) {
                $args = array();
            } else {
                $args = explode(',', $args);

                $args = array_map(function ($v) {
                    if (filter_var($v, FILTER_VALIDATE_INT)) {
                        $v = (int)$v;
                    } elseif (filter_var($v, FILTER_VALIDATE_FLOAT)) {
                        $v = (float)$v;
                    }
                    return $v;
                }, $args);
            }
        }

        if (!$delay or filter_var($delay, FILTER_VALIDATE_INT)) {
            $delay = (int)$delay;
        } else {
            $this->log('Delay option "'.$delay.'" is invalid type "'.gettype($delay).'", value must be an integer.', Logger::ERROR);
            return;
        }

        if ($delay) {
            if ($job = Resque::later($delay, $job, $args, $queue)) {
                $this->log('Job <pop>'.$job.'</pop> will be queued at <pop>'.date('r', $job->getDelayedTime()).'</pop> on <pop>'.$job->getQueue().'</pop> queue.');
                return;
            }
        } else {
            if ($job = Resque::push($job, $args, $queue)) {
                $this->log('Job <pop>'.$job.'</pop> added to <pop>'.$job->getQueue().'</pop> queue.');
                return;
            }
        }

        $this->log('Error, job was not queued. Please try again.', Logger::ERROR);
    }
}
