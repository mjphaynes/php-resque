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
 * This job fails because of PHP timeout
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class FailLong
{
    public function perform($args)
    {
        while (true) {
            // loop forever (until the timeout is reached)
        }
    }
}
