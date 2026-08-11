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
}
