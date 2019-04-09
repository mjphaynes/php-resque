php-resque
===========================================

php-resque (pronounced like "rescue") is a Redis-backed library for creating
background jobs, placing those jobs on multiple queues, and processing them later.

---

#### Contents ####

* [Background](#background)
* [Requirements](#requirements)
* [Getting Started](#getting-started)
* [Jobs](#jobs)
    * [Defining Jobs](#defining-jobs)
    * [Queueing Jobs](#queueing-jobs)
    * [Delaying Jobs](#delaying-jobs)
    * [Job Statuses](#job-statuses)
* [Workers](#workers)
    * [Signals](#signals)
    * [Forking](#forking)
    * [Process Titles](#process-titles)
    * [Autoload Job Classes](#autoload-job-classes)
* [Commands & Options](#commands--options)
* [Logging](#logging)
* [Event/Hook System](#eventhook-system)
* [Configuration Options](#configuration-options)
* [Redis](#redis)
* [Contributors](#contributors)

---

## Background ##

This version of php-resque is based on the work originally done by [chrisboulton](https://github.com/chrisboulton/php-resque) where
he ported the [ruby version](https://github.com/resque/resque) of the same name that was created by [GitHub](http://github.com/blog/542-introducing-resque).

The reasoning behind rewriting the previous work is to add better support for horizontal scaling of worker
servers and to improve job failure tolerance to create a very highly available system. Integration
with Monolog means that very verbose logging is achievable which makes it far easier to solve bugs across
distributed systems. And an extensive events/hooks system enables deeper integration and stats gathering.

This version provides features such as:

* Workers can be distributed between multiple machines.
* Resilient to memory leaks (jobs are run on forked processes).
* Expects and logs failure.
* Logging uses Monolog.
* Ability to push Closures to queues.
* Job status and output tracking.
* Jobs will fail cleanly if out of memory or maximum execution time is reached.
* Will mark a job as failed, if a forked child running a job does not exit with a status code as 0.
* Has built in event system to enable hooks for deep integration.
* Support for priorities (queues).

_This version is not a direct port of Github's Resque and therefore is not compatible with it, or their web interface._
_A web interface for this version is currently in development, if you want to know more or help out then drop me a line._


## Requirements ##

You must have the following installed in order to run php-resque:

* [Redis](http://redis.io/)
* [PHP 5.3+](http://php.net/)
* [PCNTL PHP extension](http://php.net/manual/en/book.pcntl.php)
* [Composer](http://getcomposer.org/)

Optional, but recommended:
* [Phpiredis 1.0+](https://github.com/nrk/phpiredis)
* [proctitle PECL Package](http://pecl.php.net/package/proctitle)

---

## Getting Started ##

The easiest way to work with php-resque is when it's installed as a [Composer package](https://packagist.org/packages/mjphaynes/php-resque) inside your project.
[Composer (http://getcomposer.org/)](http://getcomposer.org/) isn't strictly required, but makes life a lot easier.

Add php-resque to your application's `composer.json` file:
```json
{
    "require": {
        "mjphaynes/php-resque": "2.1.*"
    }
}
```

Navigate to your project root and run:
```
$ php composer.phar install
```

If you haven't already, add the Composer autoloader to your project's bootstrap:
```php
require 'vendor/autoload.php';
```


## Jobs ##

### Defining Jobs ###

Each job should be in it's own class, and include a `perform` method.
```php
class MyJob {

    public function setUp() {
        // Set up environment for this job
    }

	public function perform($args) {
        // Perform some job
    }

    public function tearDown() {
        // Remove environment for this job
    }

}
```

When the job is run, the class will be instantiated and any arguments will be sent as
arguments to the perform method. The current job instance (`Resque\Job`) is passed
to the perform method as the second argument.

Any exception thrown by a job will result in the job failing - be careful here and make
sure you handle the exceptions that shouldn't result in a job failing. If you want to
cancel a job (instead of having it fail) then you can throw a `Resque\Exception\Cancel`
exception and the job will be marked as cancelled.

Jobs can also have `setUp` and `tearDown` methods. If a `setUp` method is defined, it will
be called before the perform method is run. The `tearDown` method if defined, will be
called after the job finishes. If an exception is thrown int the `setUp` method the perform
method will not be run. This is useful for cases where you have different jobs that require
the same bootstrap, for instance a database connection.

### Queueing Jobs ###

To add a new job to the queue use the `Resque::push` method.

```php
$job = Resque::push('MyJob', array('arg1', 'arg2'));
```

The first argument is the fully resolved classname for your job class (if you're wondering how
php-resque knows about your job classes see [autoloading job classes](#autoload-job-classes)).
The second argument is an array of any arguments you want to pass through to the job class.

It is also possible to push a Closure onto the queue. This is very convenient for quick,
simple tasks that need to be queued. When pushing Closures onto the queue, the `__DIR__`
and `__FILE__` constants should not be used.

```php
$job = Resque::push(function($job) {
    echo 'This is a inline job #'.$job->getId().'!';
});
```

It is possible to push a job onto another queue (default queue is called `default`) by passing
through a third parameter to the `Resque::push` method which contains the queue name.

```php
$job = Resque::push('SendEmail', array(), 'email');
```


### Delaying Jobs ###

It is possible to schedule a job to run at a specified time in the future using the `Resque::later`
method. You can do this by either passing through an `int` or a `DateTime` object.

```php
$job = Resque::later(60, 'MyJob', array());
$job = Resque::later(1398643990, 'MyJob', array());
$job = Resque::later(new \DateTime('+2 mins'), 'MyJob', array());
$job = Resque::later(new \DateTime('2014-07-08 11:14:15'), 'MyJob', array());
```

If you pass through an integer and it is smaller than `94608000` seconds (3 years) php-resque will
assume you want a time relative to the current time (I mean, who delays jobs for more than 3 years
anyway??). Note that you must have a worker running at the specified time in order for the job to run.


### Job Statuses ###

php-resque tracks the status of a job. The status information will allow you to check if a job is in the queue, currently being run, failed, etc.
To track the status of a job you must capture the job id of a pushed job.

```php
$job = Resque::push('MyJob');
$jobId = $job->getId();
```

To fetch the status of a job:

```php
$job = Resque\Job::load($jobId);
$status = $job->getStatus();
```

Job statuses are defined as constants in the Resque\Job class. Valid statuses are:

* `Resque\Job::STATUS_WAITING`   - Job is still queued
* `Resque\Job::STATUS_DELAYED`   - Job is delayed
* `Resque\Job::STATUS_RUNNING`   - Job is currently running
* `Resque\Job::STATUS_COMPLETE`  - Job is complete
* `Resque\Job::STATUS_CANCELLED` - Job has been cancelled
* `Resque\Job::STATUS_FAILED`    - Job has failed
* `false` - Failed to fetch the status - is the id valid?

Statuses are available for up to 7 days after a job has completed or failed, and are then automatically expired.
This timeout can be changed in the configuration file.

## Workers ##

To start a worker navigate to your project root and run:

```
$ bin/resque worker:start
```

Note that once this worker has started, it will continue to run until it is manually stopped.
You may use a process monitor such as [Supervisor](http://supervisord.org/) to run the worker
as a background process and to ensure that the worker does not stop running.

If the worker is a background task you can stop, pause & restart the worker with the following commands:

```
$ bin/resque worker:stop
$ bin/resque worker:pause
$ bin/resque worker:resume
```

The commands take inline configuration options as well as reading from a [configuration file](https://github.com/mjphaynes/php-resque/blob/master/docs/configuration.md#file).

For instance, to specify that the worker only processes jobs on the queues named `high` and `low`, as well as allowing
a maximum of `30MB` of memory for the jobs, you can run the following:

```
$ bin/resque worker:start --queue=high,low --memory=30 -vvv
```

Note that this will check the `high` queue first and then the `low` queue, so it is possible to facilitate job queue
priorities using this. To run all queues use `*` - this is the default value. The `-vvv` enables very verbose
logging. To silence any logging the `-q` flag is used.

For more commands and full list of options please see
the [commands](https://github.com/mjphaynes/php-resque/blob/master/docs/commands.md) documentation.

In addition, if the workers are running on a different host, you may trigger a graceful shutdown of a worker remotely via the data in Redis. For example:

```php
foreach(Resque\Worker::allWorkers() as $worker) {
    $worker->shutdown();
}
```

### Signals ###

Signals work on supported platforms. Signals sent to workers will have the following effect:

* `QUIT` - Wait for child to finish processing then exit
* `TERM` / `INT` - Immediately kill child then exit
* `USR1` - Immediately kill child but don't exit
* `USR2` - Pause worker, no new jobs will be processed
* `CONT` - Resume worker

### Forking ###

When php-resque runs a job it first forks the process to a child process. This is so that if the job fails
the worker can detect that the job failed and will continue to run. The forked child will always exit as
soon as the job finishes.

The PECL module (http://php.net/manual/en/book.pcntl.php) must be installed to use php-resque.

### Process Titles ###

Updating the process title of the worker is useful because it indicates  what the worker is doing,
and any forked children also set their process title with the job being run.
This helps identify running processes on the server and their php-resque status.

_Unfortunately PHP does not have the ability to update process titles installed by default._

A PECL module (http://pecl.php.net/package/proctitle) exists that adds this funcitonality to PHP,
so if you'd like process titles updated, install the PECL module as well. php-resque will detect and use it.


### Autoload Job Classes ###

Getting your application underway also requires telling the worker about your job classes,
by means of either an autoloader or including them. If you're using Composer then it will
be relatively straightforward to add your job classes there.

Alternatively you can do so in the `config.yml` file or by setting the include argument:

```
$ bin/resque worker:start --include=/path/to/your/include/file.php
```

There is an example of how this all works in the `examples/` folder in this project.


## Commands & Options ##

For the full list of php-resque commands and their associated arguments please
see the [commands documentation](https://github.com/mjphaynes/php-resque/blob/master/docs/commands.md).


## Logging ##

php-resque is integrated with [Monolog](https://github.com/Seldaek/monolog) which enables extensive logging abilities. For full documentation
please see the [logging documentation](https://github.com/mjphaynes/php-resque/blob/master/docs/logging.md).


## Event/Hook System ##

php-resque has an extensive events/hook system to allow developers deep integration with the library without
having to modify any core files. For full documentation and list of all events please see the [hooks documentation](https://github.com/mjphaynes/php-resque/blob/master/docs/hooks.md).


## Configuration Options ##

For a complete list of all configuration options please
see the [configuration documentation](https://github.com/mjphaynes/php-resque/blob/master/docs/configuration.md).


## Redis ##

You can either set the Redis connection details inline or in the [configuration file](https://github.com/mjphaynes/php-resque/blob/master/docs/configuration.md).
To set when running a command:

```
$ bin/resque [command] --host=<hostname> --port=<port>
```


---

## Contributors ##

Contributing to the project would be a massive help in maintaining and extending the script.
If you're interested in contributing, issue a pull request on Github.

* [mjphaynes](https://github.com/mjphaynes)
* [chrisboulton](https://github.com/chrisboulton) (original port)
* [Project contributors](https://github.com/mjphaynes/php-resque/graphs/contributors)
