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
use ReflectionClass;
use ReflectionProperty;
use Resque\Redis;
use Resque\Worker;

final class WorkerCleanupTest extends TestCase
{
    /**
     * @var RecordingRedis|null
     */
    private $redis = null;

    protected function setUp(): void
    {
        $this->redis = (new ReflectionClass(RecordingRedis::class))->newInstanceWithoutConstructor();

        $this->setRedisInstance($this->redis);
    }

    protected function tearDown(): void
    {
        $this->setRedisInstance(null);

        $this->redis = null;
    }

    /**
     * cleanup() expires every `worker:<host>:*` hash whose id is not in the `workers` set. Since
     * register() adds the id to the set before it writes the hash, a hash listed before the
     * snapshot is taken is guaranteed to have its id in that snapshot — so only a genuine orphan
     * is left over.
     *
     * Taking the snapshot first breaks that: a worker registering between the two reads is absent
     * from the snapshot but present in the key list, and cleanup() puts a TTL on the hash of a
     * live worker. When it expires the worker is left running with no registration hash.
     */
    public function testTheWorkerHashesAreListedBeforeTheWorkersAreSnapshotted(): void
    {
        (new Worker('queue', false))->cleanup();

        $listedKeys = $this->redis->firstCallTo('keys');
        $snapshot   = $this->redis->firstCallTo('smembers', Worker::redisKey());

        $this->assertNotNull($listedKeys, 'cleanup() did not list the worker hashes');
        $this->assertNotNull($snapshot, 'cleanup() did not snapshot the workers');

        $this->assertLessThan(
            $snapshot,
            $listedKeys,
            'cleanup() must list the worker hashes before it snapshots the workers, '
            . 'otherwise it expires the hash of a worker that registers between the two reads'
        );
    }

    /**
     * @param RecordingRedis|null $instance
     */
    private function setRedisInstance($instance): void
    {
        $property = new ReflectionProperty(Redis::class, 'instance');
        $property->setAccessible(true);
        $property->setValue(null, $instance);
    }
}

/**
 * Answers every command with an empty result and records the order the commands arrived in, so a
 * test can assert how a method reads Redis without a server to read from.
 */
final class RecordingRedis extends Redis
{
    /**
     * @var array<int, array{method: string, key: string}>
     */
    private $calls = [];

    public function __call(string $method, array $parameters)
    {
        $this->calls[] = [
            'method' => $method,
            'key'    => isset($parameters[0]) && is_string($parameters[0]) ? $parameters[0] : '',
        ];

        return [];
    }

    /**
     * The position of the first `$method` call, optionally only counting the ones made against
     * `$key`, or null if it was never called.
     */
    public function firstCallTo(string $method, ?string $key = null): ?int
    {
        foreach ($this->calls as $position => $call) {
            if ($call['method'] === $method && ($key === null || $call['key'] === $key)) {
                return $position;
            }
        }

        return null;
    }
}
