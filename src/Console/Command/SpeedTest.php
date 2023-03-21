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

use Resque\Redis;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Performs a raw speed test
 *
 * @package Resque
 * @author Michael Haynes
 */
final class SpeedTest extends Command
{
    protected function configure(): void
    {
        $this->setName('speed:test')
            ->setDefinition($this->mergeDefinitions([
                new InputOption('time', 't', InputOption::VALUE_REQUIRED, 'Length of time to run the test for', 10),
            ]))
            ->setDescription('Performs a speed test on php-resque to see how many jobs/second it can compute')
            ->setHelp('Performs a speed test on php-resque to see how many jobs/second it can compute');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists(Process::class)) {
            throw new \Exception('The Symfony process component is required to run the speed test.');
        }

        $redisNamespace = 'resque:speedtest';
        $logFile = RESQUE_DIR.'/speed.log';

        Redis::setConfig(['namespace' => $redisNamespace]);

        $testTime = (int)$input->getOption('time') ?: 5;

        @unlink($logFile);
        $process = new Process([
            RESQUE_BIN_DIR.'/resque', 'worker:start',
            '-I', RESQUE_DIR.'/autoload.php',
            '--scheme', $this->config['scheme'],
            '-H', $this->config['host'],
            '-p', $this->config['port'],
            '--namespace', $redisNamespace,
            '--log', $logFile,
            '-b', true,
            '-i', 1,
            '-vv',
        ]);

        $start = microtime(true);
        $process->start();

        do {
            $this->setProgress($output, \Resque\Resque::stats(), $testTime, $start);
            usleep(500);
        } while ($process->isRunning() and $testTime > (microtime(true) - $start));

        $process->stop(0, SIGTERM);

        if (!$process->isSuccessful()) {
            [$error] = explode('Exception trace:', $process->getErrorOutput());

            $output->write('<error>'.$error.'</error>');
        }

        // Clear down Redis
        $redis = Redis::instance();
        $keys = $redis->keys('*');
        foreach ($keys as $key) {
            $redis->del($key);
        }

        return self::SUCCESS;
    }

    // http://www.tldp.org/HOWTO/Bash-Prompt-HOWTO/x361.html
    public function setProgress(OutputInterface $output, array $stats, float $testTime, float $start): void
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

        $replace = [
            '%title%'    => $exec_time == $testTime ? 'Finished' : 'Running',
            '%progress%' => $progress_bar,
            '%jobs%'     => @$stats['processed'].' job'.(@$stats['processed'] == 1 ? '' : 's'),
            '%in%'       => $exec_time.'s'.($progress_complete_length == $progress_length ? '' : ' ('.$testTime.'s test)'),
            '%speed%'    => round($rate, 1).' jobs/s',
            '%time%'     => $rate > 0 ? round(1 / $rate * 1000, 1).' ms' : '-',
            '%clr%'      => "\033[K",
        ];

        $output->writeln(($reset ? "\033[6A" : '').strtr($display, $replace));

        !$reset and $reset = true;
    }
}
