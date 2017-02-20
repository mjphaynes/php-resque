<?php
/**
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This job prints out some text
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class HelloWorld
{
    public function perform($args)
    {
        $text = <<<TEXT
	    __  __     ____         _       __           __    __   __
	   / / / /__  / / /___     | |     / /___  _____/ /___/ /  / /
	  / /_/ / _ \/ / / __ \    | | /| / / __ \/ ___/ / __  /  / /
	 / __  /  __/ / / /_/ /    | |/ |/ / /_/ / /  / / /_/ /  /_/
	/_/ /_/\___/_/_/\____/     |__/|__/\____/_/  /_/\__,_/  (_)


TEXT;
        echo $text;
    }
}
