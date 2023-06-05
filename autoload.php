<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Autoload file for php-resque speedtest feature.
 */

use Resque\Event;
use Resque\Logger;
use Resque\Resque;

// Test job class
class TestJob
{
    public function perform($args): void
    {
        // Don't do anything
    }
}

// Lets record the forking time
Event::listen([Event::WORKER_FORK, Event::WORKER_FORK_CHILD], function ($event, $job) use ($logger): void {
    static $start = 0;

    if ($event === Event::WORKER_FORK_CHILD) {
        $exec = microtime(true) - $start;
        $logger->log('Forking process took '.round($exec * 1000, 2).'ms', Logger::DEBUG);
    } else {
        $start = microtime(true);
    }
});

// When the job is about to be run, queue another one
Event::listen(Event::JOB_PERFORM, function ($event, $job) use ($logger): void {
    Resque::push('TestJob');
});

// Add a few jobs to the default queue
for ($i = 0; $i < 10; $i++) {
    Resque::push('TestJob');
}
