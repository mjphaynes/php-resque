<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Commands\Socket;

use Resque;
use Resque\Job;
use Resque\Host;
use Resque\Redis;
use Resque\Worker;
use Resque\Socket;
use Resque\Helpers\Util;
use Resque\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TCP receive command class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Receive extends Command
{
    protected function configure()
    {
        $this->setName('socket:receive')
            ->setDefinition($this->mergeDefinitions(array(
                new InputOption('listenhost', null, InputOption::VALUE_OPTIONAL, 'The host to listen on.', '0.0.0.0'),
                new InputOption('listenport', null, InputOption::VALUE_OPTIONAL, 'The port to listen on.', Socket\Server::DEFAULT_PORT),
                new InputOption('listenretry', null, InputOption::VALUE_NONE, 'If can\'t bind address or port then retry every <timeout> seconds until it can.'),
                new InputOption('listentimeout', 't', InputOption::VALUE_OPTIONAL, 'The retry timeout time (seconds).', 10),
            )))
            ->setDescription('Listens to socket in order to receive events')
            ->setHelp('Listens to socket in order to receive events')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host    = $this->getConfig('listenhost');
        $port    = $this->getConfig('listenport');
        $retry   = $this->getConfig('listenretry');
        $timeout = $this->getConfig('listentimeout');
        $server  = new Socket\Server(array('ip' => $host, 'port' => $port), $this->logger);

        do {
            try {
                $server->start();
            } catch (Socket\Exception $e) {
                if ($retry) {
                    $server->log('<error>Socket server failure: "'. $e->getMessage().'". Retrying in '.$timeout.' seconds...</error>');
                    sleep($timeout);
                    continue;
                }
                throw $e;
            }

            break;
        } while ($retry);

        $command = $this;

        $server->onConnect(function ($server, &$client, $input) {
            //
        });

        $server->onDisconnect(function ($server, &$client, $message) {
            $server->send($client, $message);
        });

        $server->onReceive(function ($server, &$client, $input) use ($command) {
            if (!($data = json_decode(trim($input), true))) {
                $data = trim($input);
            }

            if (is_array($data)) {
                $cmd = $data['cmd'];
                unset($data['cmd']);
            } else {
                try {
                    $input = new StringInput($data, new InputDefinition(array(
                        new InputArgument('cmd', InputArgument::REQUIRED),
                        new InputArgument('id', InputArgument::OPTIONAL),
                        new InputOption('force', 'f', InputOption::VALUE_NONE),
                        new InputOption('json', 'j', InputOption::VALUE_NONE),
                    )));

                    $cmd  = $input->getArgument('cmd');
                    $data = array(
                        'id'    => $input->getArgument('id'),
                        'force' => $input->getOption('force'),
                        'json'  => $input->getOption('json'),
                    );
                } catch (\Exception $e) {
                    $server->send($client, 'Command error: '.$e->getMessage());
                    return;
                }
            }

            switch (strtolower($cmd)) {
                case 'shell':
                    $server->send($client, 'Connected to php-resque on '.$server.'. To quit, type "quit"');
                    break;

                case 'workers':
                    $workers = Worker::hostWorkers();

                    if (empty($workers)) {
                        $response = array('ok' => 0, 'message' => 'There are no workers running on this host.');
                        $server->send($client, $data['json'] ? json_encode($response) : $response['message']);
                        return;
                    }

                    if ($data['json']) {
                        $response = array('ok' => 1, 'data' => array());

                        foreach ($workers as $i => $worker) {
                            $response['data'][] = $worker->getPacket();
                        }

                        $server->send($client, json_encode($response));
                    } else {
                        $table = new Resque\Helpers\Table($command);
                        $table->setHeaders(array('#', 'Status', 'ID', 'Running for', 'Running job', 'P', 'C', 'F', 'Interval', 'Timeout', 'Memory (Limit)'));

                        foreach ($workers as $i => $worker) {
                            $packet = $worker->getPacket();

                            $table->addRow(array(
                                $i + 1,
                                Worker::$statusText[$packet['status']],
                                (string)$worker,
                                Util::human_time_diff($packet['started']),
                                !empty($packet['job_id']) ? $packet['job_id'].' for '.Util::human_time_diff($packet['job_started']) : '-',
                                $packet['processed'],
                                $packet['cancelled'],
                                $packet['failed'],
                                $packet['interval'],
                                $packet['timeout'],
                                Util::bytes($packet['memory']).' ('.$packet['memory_limit'].' MB)',
                            ));
                        }

                        $server->send($client, (string)$table);
                    }

                    break;
                case 'worker:start':
                case 'worker:restart':
                    $response = array('ok' => 0, 'message' => 'This command is not yet supported remotely.');
                    $server->send($client, $data['json'] ? json_encode($response) : $response['message']);
                    break;
                case 'worker:pause':
                case 'worker:resume':
                case 'worker:stop':
                case 'worker:cancel':
                    $valid_id = false;

                    $id = preg_replace('/[^a-z0-9\*:,\.;-]/i', '', $data['id']);

                    if (!empty($id)) {
                        if (false === ($worker = Resque\Worker::hostWorker($id))) {
                            if ($data['json']) {
                                $response = array('ok' => 0, 'message' => 'Invalid worker id.');
                                $server->send($client, json_encode($response));
                            } else {
                                $server->send($client, "Usage:\n\t{$cmd} <worker_id>\n\n".
                                    "Help: You must specify a valid worker id, to get a \n".
                                    "list of workers use the \"workers\" command.");
                            }
                            return;
                        }

                        $workers = array($worker);
                    } else {
                        $workers = Resque\Worker::hostWorkers();

                        if (empty($workers)) {
                            $response = array('ok' => 0, 'message' => 'There are no workers on this host.');
                            $server->send($client, $data['json'] ? json_encode($response) : $response['message']);
                            return;
                        }
                    }

                    $cmd = $data['force'] ? 'worker:term' : $cmd;

                    $signals = array(
                        'worker:pause'  => SIGUSR2,
                        'worker:resume' => SIGCONT,
                        'worker:stop'   => SIGQUIT,
                        'worker:term'   => SIGTERM,
                        'worker:cancel' => SIGUSR1,
                    );

                    $messages = array(
                        'worker:pause'  => 'Paused worker %s',
                        'worker:resume' => 'Resumed worker %s',
                        'worker:stop'   => 'Stopped worker %s',
                        'worker:term'   => 'Force stopped worker %s',
                        'worker:cancel' => 'Cancelled running job on worker %s',
                    );

                    $response = array('ok' => 1, 'data' => array());

                    foreach ($workers as $worker) {
                        $pid = $worker->getPid();

                        if ($cmd == 'worker:cancel') {
                            $packet  = $worker->getPacket();
                            $job_pid = (int)$packet['job_pid'];

                            if ($job_pid and posix_kill($job_pid, 0)) {
                                $pid = $job_pid;
                            } else {
                                $response['data'][] = array('ok' => 0, 'message' => 'The worker '.$worker.' has no running job to cancel.');
                                continue;
                            }
                        }

                        if (posix_kill($pid, $signals[$cmd])) {
                            $response['data'][] = array('ok' => 1, 'message' => sprintf($messages[$cmd], $worker));
                        } else {
                            $response['data'][] = array('ok' => 0, 'message' => 'There was an error sending the signal, please try again.');
                        }
                    }

                    $server->send($client, $data['json'] ? json_encode($response) : implode(PHP_EOL, array_map(function ($d) {
                        return $d['message'];
                    }, $response['data'])));

                    break;
                case 'job:queue':
                    $response = array('ok' => 0, 'message' => 'Cannot queue remotely as it makes no sense. Use command `resque job:queue <job> <args> [--queue=<queue> [--delay=<delay>]]` locally.');
                    $server->send($client, $data['json'] ? json_encode($response) : $response['message']);

                    break;
                case 'cleanup':
                    $host = new Host();
                    $cleaned_hosts = $host->cleanup();

                    $worker = new Worker('*');
                    $cleaned_workers = $worker->cleanup();
                    $cleaned_hosts = array_merge_recursive($cleaned_hosts, $host->cleanup());

                    $cleaned_jobs = Job::cleanup();

                    if ($data['json']) {
                        $response = array('ok' => 1, 'data' => array_merge($cleaned_hosts, $cleaned_workers, $cleaned_jobs));
                        $server->send($client, json_encode($response));
                    } else {
                        $output = 'Cleaned hosts: '.json_encode($cleaned_hosts['hosts']).PHP_EOL.
                            'Cleaned workers: '.json_encode(array_merge($cleaned_hosts['workers'], $cleaned_workers)).PHP_EOL.
                            'Cleaned '.$cleaned_jobs['zombie'].' zombie job'.($cleaned_jobs['zombie'] == 1 ? '' : 's').PHP_EOL.
                            'Cleared '.$cleaned_jobs['processed'].' processed job'.($cleaned_jobs['processed'] == 1 ? '' : 's');
                    }

                    $server->send($client, $output);

                    break;
                case 'shutdown':
                    $server->shutdown();
                    break;
                case 'quit':
                case 'exit':
                    $server->disconnect($client);
                    break;
                default:
                    $response = array('ok' => 0, 'message' => 'Sorry, I don\'t know what to do with command "'.$cmd.'".');
                    $server->send($client, $data['json'] ? json_encode($response) : $response['message']);
                    break;
            }
        });

        $server->run();
    }

    public function pollingConsoleOutput()
    {
        return true;
    }
}
