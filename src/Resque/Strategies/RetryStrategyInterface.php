<?php
/**
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque;

use Resque\Helpers\Stats;
use Resque\Helpers\Output;

/**
 * Interface RetryStrategyInterface
 * @package Resque
 */
interface RetryStrategyInterface
{
    /**
     * @param $attempt
     *
     * @return int      Time to wait in ms
     */
    public function getWaitTime($attempt);

}
