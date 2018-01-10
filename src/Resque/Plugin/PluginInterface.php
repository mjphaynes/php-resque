<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Plugin;

use Resque\Worker;

interface PluginInterface
{
    /**
     * @param Worker $worker
     */
    public function init($worker);
}
