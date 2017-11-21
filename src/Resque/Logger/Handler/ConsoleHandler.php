<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Logger\Handler;

use Resque\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * Monolog console handler
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class ConsoleHandler extends AbstractProcessingHandler
{

    /**
     * @var OutputInterface The console output interface
     */
    protected $output;

    /**
     * @var array Map log levels to output verbosity
     */
    private $verbosityLevelMap = array(
        Logger::INFO      => OutputInterface::VERBOSITY_NORMAL,
        Logger::NOTICE    => OutputInterface::VERBOSITY_VERBOSE,
        Logger::WARNING   => OutputInterface::VERBOSITY_VERY_VERBOSE,
        Logger::DEBUG     => OutputInterface::VERBOSITY_DEBUG,

        Logger::ERROR     => OutputInterface::VERBOSITY_NORMAL,
        Logger::CRITICAL  => OutputInterface::VERBOSITY_NORMAL,
        Logger::ALERT     => OutputInterface::VERBOSITY_NORMAL,
        Logger::EMERGENCY => OutputInterface::VERBOSITY_NORMAL
    );

    /**
     * Colours: black, red, green, yellow, blue, magenta, cyan, white
     * Options: bold, underscore, blink, reverse, conceal
     *
     * @var array
     */
    private $styleMap = array(
        'info'      => array(),
        'notice'    => array(),
        'warning'   => array('yellow'),
        'debug'     => array('blue'),
        'error'     => array('white', 'red'),
        'critical'  => array('white', 'red'),
        'alert'     => array('white', 'red'),
        'emergency' => array('white', 'red'),

        'pop'       => array('green'),
        'warn'      => array('yellow'),
        'comment'   => array('yellow'),
        'question'  => array('black', 'cyan')
    );

    /**
     * @param OutputInterface $output The output interface
     * @param int             $level  The minimum logging level at which this handler will be triggered
     * @param bool            $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(OutputInterface $output, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->output = $output;

        foreach ($this->styleMap as $name => $styles) {
            $style = new \ReflectionClass('Symfony\Component\Console\Formatter\OutputFormatterStyle');
            $this->output->getFormatter()->setStyle($name, $style->newInstanceArgs($styles));

            if ($this->output instanceof ConsoleOutputInterface) {
                $this->output->getErrorOutput()->getFormatter()->setStyle($name, $style->newInstanceArgs($styles));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        return $this->updateLevel() and parent::isHandling($record);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record)
    {
        // we have to update the logging level each time because the verbosity of the
        // console output might have changed in the meantime (it is not immutable)
        return $this->updateLevel() and parent::handle($record);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        if (
            null === $this->output or
            OutputInterface::VERBOSITY_QUIET === ($verbosity = $this->output->getVerbosity()) or
            $verbosity < $this->verbosityLevelMap[$record['level']]
        ) {
            return false;
        }

        if ($record['level'] >= Logger::ERROR and $this->output instanceof ConsoleOutputInterface) {
            $this->output->getErrorOutput()->write((string)$record['formatted']);
        } else {
            $this->output->write((string)$record['formatted']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter()
    {
        $formatter = new Logger\Formatter\ConsoleFormatter;
        if (method_exists($formatter, 'allowInlineLineBreaks')) {
            $formatter->allowInlineLineBreaks(true);
        }
        return $formatter;
    }

    /**
     * Updates the logging level based on the verbosity setting of the console output.
     *
     * @return bool Whether the handler is enabled and verbosity is not set to quiet.
     */
    private function updateLevel()
    {
        if (null === $this->output or OutputInterface::VERBOSITY_QUIET === ($verbosity = $this->output->getVerbosity())) {
            return false;
        }

        $this->setLevel(Logger::DEBUG);
        return true;
    }
}
