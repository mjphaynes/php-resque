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
 * This is a quick and dirty to give you something visual when getting started
 * with php-resque. Navigate to /path/to/php-resque/examples/ in your browser
 * and you can add some test jobs to see how it all works.
 *
 * Note that you will also have to run `/path/to/bin/resque worker:start` from
 * your command line in order for php-resque to process jobs.
 */
if (!ini_get('date.timezone') or ! date_default_timezone_get()) {
    date_default_timezone_set('UTC');
}

if (file_exists($file = __DIR__ . '/../vendor/autoload.php')) {
    require_once $file;
} else {
    echo 'You need to set up the project dependencies using the following commands:' .
    'curl -s http://getcomposer.org/installer | php' .
    'php composer.phar install';
    exit(1);
}

Resque::loadConfig();

$job = false;
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'reset':
            $redis = Resque\Redis::instance();
            $keys = $redis->keys('*');
            foreach ($keys as $key) {
                $redis->del($key);
            }
            header('Location: ?');
            break;

        case 'push':
            $job = Resque::push('HelloWorld', array());
            break;
        case 'delayed':
            $job = Resque::later(mt_rand(0, 30), 'HelloWorld', array());
            break;
        case 'delayedat':
            $job = Resque::later(new \DateTime('+2 mins'), 'HelloWorld', array());
            break;
        case 'longrunning':
            $job = Resque::push('LongRunning', array());
            break;
        case 'failnoclass':
            $job = Resque::push('\Does\Not\Exist', array());
            break;
        case 'faillong':
            $job = Resque::push('FailLong', array());
            break;
        case 'failexception':
            $job = Resque::push('FailException', array());
            break;
        case 'failerror':
            $job = Resque::push('FailError', array());
            break;
        case 'closure':
            $job = Resque::push(function ($job) {
                echo 'This is an inline job! #' . $job->getId() . PHP_EOL;
            });
            break;
        case 'closure-delayed':
            $job = Resque::later(mt_rand(0, 30), function ($job) {
                echo 'This is a delayed inline job! #' . $job->getId() . PHP_EOL;
            });
            break;
    }
}

if ($job) {
    header('Location: ?id=' . $job->getId());
    exit;
}

echo '<pre><h1><a href="?">php-resque</a></h1><ul>' .
 '<li><a href="?action=reset">Reset</a></li>' .
 '<li><a href="?action=push">Push new job</a></li>' .
 '<li><a href="?action=delayed">Delayed job</a></li>' .
 '<li><a href="?action=delayedat">Delayed job in 2 mins</a></li>' .
 '<li><a href="?action=longrunning">Long running job</a></li>' .
 '<li><a href="?action=faillong">Fail due to running too long</a></li>' .
 '<li><a href="?action=failnoclass">Fail due to no class being found</a></li>' .
 '<li><a href="?action=failexception">Fail due to exception</a></li>' .
 '<li><a href="?action=failerror">Fail due to fatal error</a></li>' .
 '<li><a href="?action=closure">Push closure</a></li>' .
 '<li><a href="?action=closure-delayed">Delayed closure</a></li>' .
 '</ul>';

$rep = 150;
echo str_repeat('=', $rep) . PHP_EOL;

echo 'Resque stats:  ' . json_encode(Resque::stats()) . PHP_EOL;
echo 'Hosts:         ' . json_encode(Resque\Redis::instance()->smembers('hosts')) . PHP_EOL;
echo 'Workers:       ' . json_encode(Resque\Redis::instance()->smembers('workers')) . PHP_EOL;
echo 'Queues:        ' . json_encode(Resque\Redis::instance()->smembers('queues')) . PHP_EOL;
echo 'Default queue: ' . json_encode(Resque\Redis::instance()->hgetall('queue:default:stats')) . PHP_EOL;
echo 'Time:          ' . json_encode(array(time(), date('r'))) . PHP_EOL;

echo str_repeat('=', $rep) . PHP_EOL;

$id = isset($_GET['id']) ? preg_replace('/[^0-9a-z]/i', '', $_GET['id']) : '';
if (!empty($id)) {
    if ($job = Resque::job($id)) {
        print_r($job->getPacket());
        echo '<a href="?id=' . $id . '">Refresh</a>';
    } else {
        echo 'Job #' . $id . ' not found';
    }
}

list_jobs('Default queue queued jobs', Resque\Redis::instance()->lrange('queue:default', 0, -1));
foreach (array('delayed', 'running', 'processed', 'failed', 'cancelled') as $status) {
    list_jobs(
        'Default queue ' . $status . ' jobs',
        $djobs = Resque\Redis::instance()->zrevrangebyscore('queue:default:' . $status, strtotime('+1 year'), 0)
    );
}

function list_jobs($title, $jobs)
{
    global $rep;
    echo PHP_EOL . str_repeat('=', $rep) . PHP_EOL . $title . ': ' . PHP_EOL;

    if (count($jobs)) {
        foreach ($jobs as $i => $job) {
            $payload = json_decode($job, true);
            echo "\t" . str_pad(($i + 1) . '.', 4) . '<a href="?id=' . $payload['id'] . '">' . $job . '</a>' . PHP_EOL;
        }
    } else {
        echo "\t-";
    }
}
