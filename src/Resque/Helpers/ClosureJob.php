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
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class ClosureJob
{

    /**
     * Fire the Closure based queue job.
     *
     * @param  array       $data
     * @param  \Resque\Job $job
     * @return void
     */
    public function perform($data, $job)
    {
        $closure = unserialize($data['closure']);
        $closure($job);
    }
}
