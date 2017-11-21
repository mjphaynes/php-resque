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
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Performs a raw speed test
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class SpeedTest extends Command
{
    protected function configure()
    {
        $this->setName('speed:test')
            ->setDefinition($this->mergeDefinitions(array(
                new InputOption('time', 't', InputOption::VALUE_REQUIRED, 'Length of time to run the test for', 10),
            )))
            ->setDescription('Performs a speed test on php-resque to see how many jobs/second it can compute')
            ->setHelp('Performs a speed test on php-resque to see how many jobs/second it can compute')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Resque\Redis::setConfig(array('namespace' => 'resque:speedtest'));

        $testTime = (int)$input->getOption('time') ?: 5;

        @unlink(RESQUE_DIR.'/test/speed/output.log');
        $process = new Process(RESQUE_BIN_DIR.'/resque worker:start -c '.RESQUE_DIR.'/test/speed/config.yml');

        $start = microtime(true);
        $process->start();

        do {
            $this->setProgress($output, Resque::stats(), $testTime, $start);
            usleep(500);
        } while ($process->isRunning() and $testTime > (microtime(true) - $start));

        $process->stop(0, SIGTERM);

        if (!$process->isSuccessful()) {
            list($error) = explode('Exception trace:', $process->getErrorOutput());

            $output->write('<error>'.$error.'</error>');
        }

        // Clear down Redis
        $redis = Resque\Redis::instance();
        $keys = $redis->keys('*');
        foreach ($keys as $key) {
            $redis->del($key);
        }
    }

    // http://www.tldp.org/HOWTO/Bash-Prompt-HOWTO/x361.html
    public function setProgress(OutputInterface $output, $stats, $testTime, $start)
    {
        static $reset = false;

        $exec_time = round(microtime(true) - $start);
        $rate = @$stats['processed'] / max($exec_time, 1);

        $progress_length = 35;
        $progress_percent = (microtime(true) - $start) / $testTime;

        $progress_bar = str_repeat('<comment>=</comment>', $progress_complete_length = round($progress_percent * $progress_length));
        $progress_bar .= $progress_complete_length == $progress_length ? '' : '<pop>></pop>';
        $progress_bar .= str_repeat('-', max($progress_length - $progress_complete_length - 1, 0));
        $progress_bar .= $progress_complete_length == $progress_length ? '' : ' '.round($progress_percent * 100).'%';

        $display = <<<STATS
<comment>%title% php-resque speed test</comment>%clr%
%progress%%clr%
Time:         <pop>%in%</pop>%clr%
Processed:    <pop>%jobs%</pop>%clr%
Speed:        <pop>%speed%</pop>%clr%
Avg job time: <pop>%time%</pop>%clr%
STATS;

        $replace = array(
            '%title%'    => $exec_time == $testTime ? 'Finished' : 'Running',
            '%progress%' => $progress_bar,
            '%jobs%'     => @$stats['processed'].' job'.(@$stats['processed'] == 1 ? '' : 's'),
            '%in%'       => $exec_time.'s'.($progress_complete_length == $progress_length ? '' : ' ('.$testTime.'s test)'),
            '%speed%'    => round($rate, 1).' jobs/s',
            '%time%'     => $rate > 0 ? round(1 / $rate * 1000, 1).' ms' : '-',
            '%clr%'      => "\033[K",
        );

        $output->writeln(($reset ? "\033[6A" : '').strtr($display, $replace));

        !$reset and $reset = true;
    }
}
