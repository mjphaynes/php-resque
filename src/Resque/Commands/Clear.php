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
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Deletes all resque data from redis
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Clear extends Command
{
    protected function configure()
    {
        $this->setName('clear')
            ->setDefinition($this->mergeDefinitions(array(
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force without asking.'),
            )))
            ->setDescription('Clears all php-resque data from Redis')
            ->setHelp('Clears all php-resque data from Redis')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('Continuing will clear all php-resque data from Redis. Are you sure? ', false);

        if ($input->getOption('force') || $helper->ask($input, $output, $question)) {
            $output->write('Clearing Redis php-resque data... ');

            $redis = Resque\Redis::instance();

            $keys = $redis->keys('*');
            foreach ($keys as $key) {
                $redis->del($key);
            }

            $output->writeln('<pop>Done.</pop>');
        }
    }
}
