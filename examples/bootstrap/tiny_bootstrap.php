<?php

define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ?: 'dev'));
const APPLICATION_ROOT = __DIR__;

require __DIR__ . '/vendor/autoload.php';

use Pimple\Container;

$container = new Container();

require __DIR__ . '/config/resque.conf.php';

$worker = new \Resque\Worker('some_queue', true);
$worker->setPidFile('/tmp/some_queue_worker.pid');
$worker->setInterval(2);
$worker->setTimeout(60);
$worker->setMemoryLimit(128);
$worker->setLogger();
$worker->setContainer($container);

if ('dev' == APPLICATION_ENV) {
    $logger = new \Resque\Logger([new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG)]);
    $worker->setLogger($logger);
}

$worker->work();
