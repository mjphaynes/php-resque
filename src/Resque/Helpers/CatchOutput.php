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
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class CatchOutput extends \Symfony\Component\Console\Output\Output
{

    /**
     * @var string Stored output string
     */
    protected $written = '';

    /**
     * Constructor.
     *
     * @param int                           $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
     * @param bool                          $decorated Whether to decorate messages
     * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
     */
    public function __construct(
        $verbosity = self::VERBOSITY_NORMAL,
        $decorated = true,
        OutputFormatterInterface $formatter = null
    ) {
        parent::__construct($verbosity, $decorated, $formatter);
    }

    /**
     * Writes a message to the output.
     *
     * @param string|array $messages The message as an array of lines or a single string
     * @param bool         $newline  Whether to add a newline
     * @param int          $type     The type of output (one of the OUTPUT constants)
     *
     * @throws \InvalidArgumentException When unknown output type is given
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_RAW)
    {
        parent::write($messages, $newline, $type);
    }

    /**
     * Stores message in a local string
     *
     * @param string $message A message to write to the output
     * @param bool   $newline Whether to add a newline or not
     */
    protected function doWrite($message, $newline)
    {
        $this->written .= $message.($newline ? PHP_EOL : '');
    }

    /**
     * Returns written string so far
     *
     * @return string
     */
    public function written()
    {
        return $this->written;
    }
}
