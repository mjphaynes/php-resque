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

namespace Resque\Blueprint;

/**
 * Blueprint for all jobs to extend.
 *
 * @package Resque
 * @author Paul Litovka <paxanddos@gmail.com>
 */
abstract class Job
{
    /**
     * Runs any required logic before the job is performed.
     *
     * @param \Resque\Job $job Current job instance
     */
    public function setUp(\Resque\Job $job): void
    {
    }

    /**
     * Actual job logic.
     *
     * @param array       $args Arguments passed to the job
     * @param \Resque\Job $job  Current job instance
     */
    abstract public function perform(array $args, \Resque\Job $job): void;

    /**
     * Runs after the job is performed.
     *
     * @param \Resque\Job $job Current job instance
     */
    public function tearDown(\Resque\Job $job): void
    {
    }
}
