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

namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RedisTest extends TestCase
{
    private array $legacyParameters = [
        'scheme'     => 'tcp',
        'host'       => 'redis_instance_01',
        'port'       => 6379,
        'namespace'  => 'some_namespace',
        'rw_timeout' => 123,
        'phpiredis'  => true,
    ];

    private array $predisNativeParameters = [
        'config'  => [
            [
                'tcp://10.0.0.1',
                'tcp://10.0.0.2',
                'tcp://10.0.0.3',
            ],
        ],
        'options' => [
            'replication' => 'sentinel',
            'service'     => 'some_redis_cluster',
            'parameters'  => [
                'password' => 'some_secure_password',
                'database' => 10,
            ],
        ],
    ];

    private ?MockObject $predisMock = null;

    private ?MockObject $redisMock = null;

    protected function setUp(): void
    {
        $predisClassName = "\\Predis\\Client";

        $this->predisMock = $this->getMockBuilder($predisClassName)
            ->disableOriginalConstructor()
            ->setMethods(['connect'])
            ->getMock()
        ;

        $className = "\\Resque\\Redis";

        $this->redisMock = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->setMethods(['initializePredisClient'])
            ->getMock()
        ;
    }

    protected function tearDown(): void
    {
        $this->predisMock = null;
    }

    public function testConstructorShouldDoTheLegacyStuff(): void
    {
        $this->redisMock->expects($this->once())
            ->method('initializePredisClient')
            ->with(
                [
                    'scheme'             => $this->legacyParameters['scheme'],
                    'host'               => $this->legacyParameters['host'],
                    'port'               => $this->legacyParameters['port'],
                    'read_write_timeout' => $this->legacyParameters['rw_timeout'],
                ],
                [
                    'connections' => [
                        'tcp'  => 'Predis\Connection\PhpiredisStreamConnection',
                        'unix' => 'Predis\Connection\PhpiredisSocketConnection',
                    ],
                ]
            )
            ->willReturn($this->predisMock)
        ;

        $this->predisMock->expects($this->any())->method('connect');

        $this->redisMock->__construct($this->legacyParameters);
    }

    public function testConstructorShouldAcceptPredisOverride(): void
    {
        $className = "\\Resque\\Redis";

        $this->redisMock = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->setMethods(['initializePredisClient'])
            ->getMock()
        ;

        $this->redisMock->expects($this->once())
            ->method('initializePredisClient')
            ->with(
                $this->predisNativeParameters['config'],
                $this->predisNativeParameters['options']
            )
            ->willReturn($this->predisMock)
        ;

        $this->predisMock->expects($this->any())->method('connect');

        $this->redisMock->__construct(array_merge($this->legacyParameters, ['predis' => $this->predisNativeParameters]));
    }
}
