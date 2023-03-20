<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Console;

use Resque\Console\Command\Cleanup;
use Resque\Console\Command\Clear;
use Resque\Console\Command\Hosts;
use Resque\Console\Command\Job\Queue;
use Resque\Console\Command\Queues;
use Resque\Console\Command\Socket\Connect;
use Resque\Console\Command\Socket\Receive;
use Resque\Console\Command\Socket\Send;
use Resque\Console\Command\SpeedTest;
use Resque\Console\Command\Worker\Cancel;
use Resque\Console\Command\Worker\Pause;
use Resque\Console\Command\Worker\Restart;
use Resque\Console\Command\Worker\Resume;
use Resque\Console\Command\Worker\Start;
use Resque\Console\Command\Worker\Stop;
use Resque\Console\Command\Workers;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResqueApplication extends Application
{
    /**
     * Initialize the Resque console application.
     */
    public function __construct()
    {
        parent::__construct('Resque', \Resque\Resque::VERSION);

        $this->addCommands([
            new Clear(),
            new Hosts(),
            new Queues(),
            new Cleanup(),
            new Workers(),
            new Queue(),
            new Send(),
            new Receive(),
            new Connect(),
            new Start(),
            new Stop(),
            new Restart(),
            new Pause(),
            new Resume(),
            new Cancel(),
            new SpeedTest()
        ]);
    }

    /**
     * Runs the current application.
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        // always show the version information except when the user invokes the help
        // command as that already does it
        if ($input->hasParameterOption('--no-info') === false) {
            if ($input->hasParameterOption(['--help', '-h']) || ($input->getFirstArgument() && $input->getFirstArgument() !== 'list')) {
                $output->writeln($this->getLongVersion());
                $output->writeln('');
            }
        }

        return parent::doRun($input, $output);
    }
}
