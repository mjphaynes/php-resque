<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Helpers;

/**
 * Executes stored closure job
 *
 * @package Resque
 * @author Michael Haynes
 */
final class ClosureJob
{
    /**
     * Fire the Closure based queue job.
     *
     * @return void
     */
    public function perform(array $data, \Resque\Job $job): void
    {
        $closure = unserialize($data['closure']);
        $closure($job);
    }
}
