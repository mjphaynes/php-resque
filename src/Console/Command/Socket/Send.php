<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Console\Command\Socket;

use Resque\Console\Command\Command;
use Resque\Socket\Server;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TCP send command class
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class Send extends Command
{
    protected function configure(): void
    {
        $this->setName('socket:send')
            ->setDefinition($this->mergeDefinitions([
                new InputArgument('cmd', InputArgument::REQUIRED, 'The command to send to the receiver.'),
                new InputArgument('id', InputArgument::OPTIONAL, 'The id of the worker (optional; required for worker: commands).'),
                new InputOption('connecthost', null, InputOption::VALUE_OPTIONAL, 'The host to send to.', '127.0.0.1'),
                new InputOption('connectport', null, InputOption::VALUE_OPTIONAL, 'The port to send on.', Server::DEFAULT_PORT),
                new InputOption('connecttimeout', 't', InputOption::VALUE_OPTIONAL, 'The send request timeout time (seconds).', 10),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force the command.'),
                new InputOption('json', 'j', InputOption::VALUE_NONE, 'Whether to return the response in JSON format.'),
            ]))
            ->setDescription('Sends a command to a php-resque receiver socket')
            ->setHelp('Sends a command to a php-resque receiver socket');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cmd     = $input->getArgument('cmd');
        $host    = $this->getConfig('connecthost');
        $port    = $this->getConfig('connectport');
        $timeout = $this->getConfig('connecttimeout');

        if (!($fh = @fsockopen('tcp://'.$host, $port, $errno, $errstr, $timeout))) {
            $this->log('['.$errno.'] '.$errstr.' host '.$host.':'.$port, \Resque\Logger::ERROR);
            return Command::FAILURE;
        }

        stream_set_timeout($fh, 0, 500 * 1000);

        $payload = [
            'cmd'   => $cmd,
            'id'    => $input->getArgument('id'),
            'force' => $input->getOption('force'),
            'json'  => $this->getConfig('json'),
        ];

        Server::fwrite($fh, json_encode($payload));

        $response = '';
        while (($buffer = fgets($fh, 1024)) !== false) {
            $response .= $buffer;
        }

        $this->log('<pop>'.trim($response).'</pop>');

        fclose($fh);

        return Command::SUCCESS;
    }
}
