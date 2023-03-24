<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Logger\Processor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Format output for non-console output
 *
 * @package Resque
 * @author Michael Haynes
 */
class StripFormatProcessor
{
    /**
     * @var Command command instance
     */
    protected $command;

    /**
     * @var InputInterface input instance
     */
    protected $input;

    /**
     * @var OutputInterface output instance
     */
    protected $output;

    /**
     * @var array list of formatting tags to strip out
     */
    private $stripTags = [
        'info',
        'notice',
        'warning',
        'debug',
        'error',
        'critical',
        'alert',
        'emergency',
        'pop',
        'warn',
        'comment',
        'question',
    ];

    /**
     * Create a new instance
     */
    public function __construct(Command $command, InputInterface $input, OutputInterface $output)
    {
        $this->command = $command;
        $this->input   = $input;
        $this->output  = $output;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record): array
    {
        static $find = [];

        if (empty($find)) {
            foreach ($this->stripTags as $tag) {
                $find[] = '<'.$tag.'>';
                $find[] = '</'.$tag.'>';
            }
        }

        $record['message'] = str_replace($find, '', $record['message']);

        return $record;
    }
}
