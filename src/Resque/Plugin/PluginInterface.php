<?php
/**
 * Created by PhpStorm.
 * User: merlin
 * Date: 10/01/18
 * Time: 13:13
 */

namespace Resque\Plugin;


use Resque\Worker;

interface PluginInterface
{
    /**
     * @param Worker $worker
     */
    public function init($worker);

}