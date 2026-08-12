<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Tests;

use Resque\Worker;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class WorkerTest
 */
class WorkerTest extends TestCase
{
    /**
     * The signal constants come with ext-pcntl, which a command line binary has
     * and a web server does not. A default of the class would be worked out as
     * soon as a worker is made, which is what a web interface listing the
     * workers does, and on PHP 8 an undefined constant is a fatal error.
     */
    public function testMakingAWorkerDoesNotNeedTheSignalConstants()
    {
        $reflection = new ReflectionClass(Worker::class);

        $default = $reflection->getDefaultProperties();

        $this->assertSame(
            array(),
            $default['signalHandlerMapping'],
            'the signal handlers must not be a default of the class'
        );
    }
}
