<?php
/**
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This job waits for 30 seconds
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class LongRunning
{
    public function perform($args)
    {
        sleep(30);
        echo 'Done.';
    }
}
