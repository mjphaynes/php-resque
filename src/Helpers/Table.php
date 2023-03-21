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

use Symfony\Component\Console\Helper\Table as TableHelper;
use Symfony\Component\Console\Helper\TableStyle;

/**
 * Wrapper for Symfony table helper
 *
 * @package Resque
 * @author Michael Haynes
 */
final class Table
{
    /**
     * @var TableHelper
     */
    protected TableHelper $table;

    /**
     * @var CatchOutput
     */
    protected CatchOutput $output;

    /**
     * Render the table and pass the output back.
     * This is done this way because the table
     * helper dumps everything to the output and
     * there is no way to catch so have to override
     * with a special output.
     */
    public function __construct()
    {
        $this->output = new CatchOutput();

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
    public function __toString(): string
    {
        $this->table->render($this->output);

        return rtrim($this->output->written()); // Remove trailing \n
    }

    /**
     * Pass all called functions to the table helper
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return call_user_func_array([$this->table, $method], $parameters);
    }
}
