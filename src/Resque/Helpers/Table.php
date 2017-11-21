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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table as TableHelper;
use Symfony\Component\Console\Helper\TableStyle;

/**
 * Wrapper for Symfony table helper
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Table
{

    /**
     * @var TableHelper
     */
    protected $table;

    /**
     * @var CatchOutput
     */
    protected $output;

    /**
     * Render the table and pass the output back.
     * This is done this way because the table
     * helper dumps everything to the output and
     * there is no way to catch so have to override
     * with a special output.
     *
     * @param  Command $command
     * @return void
     */
    public function __construct(Command $command)
    {
        $this->output = new CatchOutput;

        $this->table = new TableHelper($this->output);
        $style = new TableStyle();
        $style->setCellHeaderFormat('<pop>%s</pop>');
        $this->table->setStyle($style);
    }

    /**
     * Render the table and pass the output back.
     * This is done this way because the table
     * helper dumps everything to the output and
     * there is no way to catch so have to override
     * with a special output.
     *
     * @return string
     */
    public function __toString()
    {
        $this->table->render($this->output);

        return rtrim($this->output->written()); // Remove trailing \n
    }

    /**
     * Pass all called functions to the table helper
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->table, $method), $parameters);
    }
}
