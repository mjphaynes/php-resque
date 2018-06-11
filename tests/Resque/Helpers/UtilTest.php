<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Tests\Helpers;

use Resque\Helpers\Util;
use PHPUnit\Framework\TestCase;

/**
 * Class UtilTest
 *
 * @author Romain DARY <romain.dary@eoko.fr>
 */
class UtilTest extends TestCase
{
    /**
     * @param string $expected   expected readable sizes
     * @param int    $bytes      size in bytes
     * @param string $force_unit a definitive unit
     * @param string $format     the return string format
     * @param bool   $si         whether to use SI prefixes or IEC
     *
     * @dataProvider getBytes
     */
    public function testBytes($expected, $bytes, $force_unit = null, $format = null, $si = true)
    {
        $this->assertSame($expected, Util::bytes($bytes, $force_unit, $format, $si));
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function getBytes()
    {
        return array(
            array('1.00 B', 1),
            array('10.00 B', 10),
            array('100.00 B', 100),
            array('1.00 kB', 1000),
            array('10.00 kB', 10000),
            array('100.00 kB', 100000),
            array('10.00 MB', 10000000),
            array('100.00 MB', 100000000),
            array('1.00 GB', 1000000000),
            array('10.00 GB', 10000000000),
            array('100.00 GB', 100000000000),
            array('1.00 TB', 1000000000000),
            array('10.00 TB', 10000000000000),
            array('100.00 TB', 100000000000000),
            array('1.00 PB', 1000000000000000),
            array('10.00 PB', 10000000000000000),
            array('100.00 PB', 100000000000000000)
        );
    }
}
