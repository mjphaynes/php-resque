<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Tests;

use stdClass;
use Resque\Event;
use Resque\Worker;

class WorkerTest extends \PHPUnit_Framework_TestCase
{
    public function testRegisterPlugin()
    {
        $plugin = $this->getMock('\Resque\Plugin\PluginInterface');
        $listener = $this->getMock(stdClass::class, array('onPluginRegistration'));

        $listener
            ->expects($this->once())
            ->method('onPluginRegistration')
            ->with(Event::PLUGIN_REGISTERED, $this->isInstanceOf($plugin));

        // Event must triggered on registration
        Event::listen(Event::PLUGIN_REGISTERED, array($listener, 'onPluginRegistration'));

        Worker::registerPlugin($plugin);
        $this->assertEquals(1, count(Worker::$plugins));

        // We go back to the previous state
        Worker::unregisterPlugin($plugin);
    }

    public function testInitPlugin()
    {
        $plugin = $this->getMock('Resque\Plugin\PluginInterface', array('init'));

        $plugin
            ->expects($this->once())
            ->method('init')
            ->with($this->isInstanceOf(Worker::class));

        Worker::registerPlugin($plugin);

        $mock = $this->getMock(stdClass::class, array('onPluginInitialization'));

        $mock
            ->expects($this->once())
            ->method('onPluginInitialization')
            ->with(Event::PLUGIN_INSTANCE, $this->isInstanceOf($plugin));

        // Event must be called on plugin initialization
        Event::listen(Event::PLUGIN_INSTANCE, array($mock, 'onPluginInitialization'));

        // We check plugin initialization
        new Worker();

        // We go back to the previous state
        Worker::unregisterPlugin($plugin);
    }

    public function testUnregisterPlugin()
    {
        $plugin = $this->getMock('Resque\Plugin\PluginInterface');
        $listener = $this->getMock(stdClass::class, array('onPluginUnregistration'));

        $listener
            ->expects($this->once())
            ->method('onPluginUnregistration')
            ->with(Event::PLUGIN_UNREGISTERED, $this->isInstanceOf($plugin));

        // Event must triggered on unregistration
        Event::listen(Event::PLUGIN_UNREGISTERED, array($listener, 'onPluginUnregistration'));

        // Initiate state
        Worker::registerPlugin($plugin);

        // We unregister the plugin
        Worker::unregisterPlugin($plugin);

        $this->assertEquals(0, count(Worker::$plugins));
    }
}
