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
 * This job fails because exception is thrown
 *
 * @package Resque
 * @author Michael Haynes
 */
class FailException
{
    public function perform($args)
    {
        throw new \Exception('Testing exception handling');
    }
}
