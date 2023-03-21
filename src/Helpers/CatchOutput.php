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

use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * Catch output and store
 *
 * @package Resque
 * @author Michael Haynes
 */
final class CatchOutput extends \Symfony\Component\Console\Output\Output
{
    /**
     * @var string Stored output string
     */
    protected string $written = '';

    /**
     * {@inheritdoc}
     */
    public function __construct(
        int $verbosity = self::VERBOSITY_NORMAL,
        bool $decorated = true,
        ?OutputFormatterInterface $formatter = null
    ) {
        parent::__construct($verbosity, $decorated, $formatter);
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, bool $newline = false, $type = self::OUTPUT_RAW): void
    {
        parent::write($messages, $newline, $type);
    }

    /**
     * Stores message in a local string
     *
     * @param string $message A message to write to the output
     * @param bool   $newline Whether to add a newline or not
     */
    protected function doWrite(string $message, bool $newline): void
    {
        $this->written .= $message.($newline ? PHP_EOL : '');
    }

    /**
     * Returns written string so far
     *
     * @return string
     */
    public function written(): string
    {
        return $this->written;
    }
}
