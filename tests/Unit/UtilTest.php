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

namespace Tests\Unit;

use Resque\Helpers\Util;
use PHPUnit\Framework\TestCase;

/**
 * Class UtilTest
 *
 * @author Romain DARY <romain.dary@eoko.fr>
 */
final class UtilTest extends TestCase
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
    public function testBytes($expected, $bytes): void
    {
        $this->assertSame($expected, Util::bytes($bytes, '', null, true));
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function getBytes(): array
    {
        return [
            ['1.00 B', 1],
            ['10.00 B', 10],
            ['100.00 B', 100],
            ['1.00 kB', 1000],
            ['10.00 kB', 10000],
            ['100.00 kB', 100000],
            ['10.00 MB', 10000000],
            ['100.00 MB', 100000000],
            ['1.00 GB', 1000000000],
            ['10.00 GB', 10000000000],
            ['100.00 GB', 100000000000],
            ['1.00 TB', 1000000000000],
            ['10.00 TB', 10000000000000],
            ['100.00 TB', 100000000000000],
            ['1.00 PB', 1000000000000000],
            ['10.00 PB', 10000000000000000],
            ['100.00 PB', 100000000000000000]
        ];
    }
}
