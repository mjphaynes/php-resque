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

use Resque\Worker;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Class WorkerTest
 */
final class WorkerTest extends TestCase
{
    /**
     * The worker settings are written into the worker packet and read back from
     * it, so a setter that disagrees with its getter silently changes the type
     * of the stored value.
     *
     * @dataProvider settingProvider
     */
    public function testSetterAndGetterAgreeOnTheType(string $setter, string $getter, string $expected): void
    {
        $parameter = (new ReflectionMethod(Worker::class, $setter))->getParameters()[0];
        $returnType = (new ReflectionMethod(Worker::class, $getter))->getReturnType();

        $this->assertNotNull($parameter->getType(), sprintf('%s() has no parameter type', $setter));
        $this->assertSame($expected, $parameter->getType()->getName(), sprintf('%s() takes the wrong type', $setter));

        $this->assertNotNull($returnType, sprintf('%s() has no return type', $getter));
        $this->assertSame($expected, $returnType->getName(), sprintf('%s() returns the wrong type', $getter));
    }

    /**
     * @dataProvider settingProvider
     */
    public function testTheDefaultMatchesTheDeclaredType(string $setter, string $getter, string $expected, string $property): void
    {
        $reflection = new ReflectionProperty(Worker::class, $property);
        $reflection->setAccessible(true);

        $default = $reflection->getDeclaringClass()->getDefaultProperties()[$property];

        $this->assertSame($expected, gettype($default) === 'integer' ? 'int' : gettype($default));
    }

    public function settingProvider(): array
    {
        return [
            'timeout' => ['setTimeout', 'getTimeout', 'int', 'timeout'],
            'interval' => ['setInterval', 'getInterval', 'int', 'interval'],
            'memory limit' => ['setMemoryLimit', 'getMemoryLimit', 'int', 'memoryLimit'],
        ];
    }

    /**
     * The signal constants come with ext-pcntl, which a command line binary has
     * and a web server does not. A default of the class would be worked out as
     * soon as a worker is made, which is what a web interface listing the
     * workers does, and on PHP 8 an undefined constant is a fatal error.
     */
    public function testMakingAWorkerDoesNotNeedTheSignalConstants(): void
    {
        $default = (new ReflectionClass(Worker::class))->getDefaultProperties()['signalHandlerMapping'];

        $this->assertSame([], $default, 'the signal handlers must not be a default of the class');
    }

    /**
     * Where there is no process control there is nothing to listen for, so the
     * worker starts without handlers rather than not starting at all.
     */
    public function testAWorkerWithoutProcessControlListensToNothing(): void
    {
        $script = 'require ' . var_export(dirname(__DIR__) . '/vendor/autoload.php', true) . ';'
            . '$worker = (new ReflectionClass(' . var_export(Worker::class, true) . '))->newInstanceWithoutConstructor();'
            . '$mapping = new ReflectionMethod($worker, "createDefaultSignalHandlerMapping");'
            . '$mapping->setAccessible(true);'
            . 'echo function_exists("pcntl_signal") ? "with" : "without", "|", json_encode($mapping->invoke($worker));';

        $command = sprintf(
            '%s -d disable_functions=pcntl_signal -r %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($script)
        );

        $this->assertSame('without|[]', trim((string)shell_exec($command)));
    }

    /**
     * The other way round: where the extension is there, every signal a worker
     * is meant to answer still has its handler.
     */
    public function testAWorkerWithProcessControlListensToTheUsualSignals(): void
    {
        if (!function_exists('pcntl_signal')) {
            $this->markTestSkipped('Process control is not available');
        }

        $worker = (new ReflectionClass(Worker::class))->newInstanceWithoutConstructor();

        $mapping = new ReflectionMethod($worker, 'createDefaultSignalHandlerMapping');
        $mapping->setAccessible(true);

        $this->assertSame([
            SIGTERM => 'sigForceShutdown',
            SIGINT  => 'sigForceShutdown',
            SIGQUIT => 'sigShutdown',
            SIGUSR1 => 'sigCancelJob',
            SIGUSR2 => 'sigPause',
            SIGCONT => 'sigResume',
            SIGPIPE => 'sigWakeUp',
        ], $mapping->invoke($worker));
    }

    /**
     * @dataProvider signalHandlerProvider
     */
    public function testEveryHandlerItNamesIsThere(string $handler): void
    {
        $this->assertTrue(
            method_exists(Worker::class, $handler),
            sprintf('the worker names %s() as a signal handler but does not have it', $handler)
        );
    }

    public function signalHandlerProvider(): array
    {
        return [
            'force shutdown' => ['sigForceShutdown'],
            'shutdown' => ['sigShutdown'],
            'cancel job' => ['sigCancelJob'],
            'pause' => ['sigPause'],
            'resume' => ['sigResume'],
            'wake up' => ['sigWakeUp'],
        ];
    }
}
