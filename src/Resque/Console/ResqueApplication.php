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

use Resque;
use Resque\Command\Cleanup;
use Resque\Command\Clear;
use Resque\Command\Hosts;
use Resque\Command\Job\Queue;
use Resque\Command\Queues;
use Resque\Command\Socket\Connect;
use Resque\Command\Socket\Receive;
use Resque\Command\Socket\Send;
use Resque\Command\SpeedTest;
use Resque\Command\Worker\Cancel;
use Resque\Command\Worker\Pause;
use Resque\Command\Worker\Restart;
use Resque\Command\Worker\Resume;
use Resque\Command\Worker\Start;
use Resque\Command\Worker\Stop;
use Resque\Command\Workers;
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
        parent::__construct('PHP Resque', Resque::VERSION);

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
            if (($input->hasParameterOption(['--help', '-h']) !== false) || ($input->getFirstArgument() !== null && $input->getFirstArgument() !== 'list')) {
                $output->writeln($this->getLongVersion());
                $output->writeln('');
            }
        }

        return parent::doRun($input, $output);
    }
}
