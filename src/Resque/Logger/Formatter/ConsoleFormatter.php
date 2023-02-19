<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Logger\Formatter;

use Monolog\Formatter\LineFormatter;

/**
 * Formatter for console output
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class ConsoleFormatter extends LineFormatter
{
    const SIMPLE_FORMAT = "%start_tag%%message%%end_tag%\n";

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        $tag = strtolower($record['level_name']);

        $record['start_tag'] = '<'.$tag.'>';
        $record['end_tag']   = '</'.$tag.'>';

        return parent::format($record);
    }
}
