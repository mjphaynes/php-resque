<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Exception;

/**
 * Resque shutdown worker job exception
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class Shutdown extends \RuntimeException
{
}
