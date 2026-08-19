<?php

declare(strict_types=1);

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Resque\Console\ResqueApplication;
use Resque\Resque;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Class ResqueApplicationTest
 */
final class ResqueApplicationTest extends TestCase
{
    public function testItDeclaresTheOptionThatTurnsTheVersionInformationOff(): void
    {
        $definition = (new ResqueApplication())->getDefinition();

        $this->assertTrue($definition->hasOption('no-info'), 'The --no-info option is not declared');
        $this->assertFalse($definition->getOption('no-info')->acceptValue(), 'The --no-info option takes a value');
    }

    public function testTheVersionInformationIsWrittenOutByDefault(): void
    {
        $tester = $this->runApplication(['command' => 'queues', '--help' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString(Resque::VERSION, $tester->getDisplay());
    }

    public function testTheVersionInformationCanBeTurnedOff(): void
    {
        $tester = $this->runApplication(['command' => 'queues', '--help' => true, '--no-info' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringNotContainsString(Resque::VERSION, $tester->getDisplay());
    }

    /**
     * The flag has to be declared as well as read: doRun() takes it off the raw
     * command line, but a command binds the input against the definition, and an
     * option that is nowhere declared ends the call there with "The --no-info
     * option does not exist".
     */
    public function testTheOptionSurvivesBeingBoundToACommand(): void
    {
        $tester = $this->runApplication(['command' => 'list', '--no-info' => true]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * Runs the application without letting it exit or swallow what went wrong.
     */
    private function runApplication(array $input): ApplicationTester
    {
        $application = new ResqueApplication();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $tester = new ApplicationTester($application);
        $tester->run($input);

        return $tester;
    }
}
