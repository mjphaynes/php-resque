[php-resque](https://github.com/mjphaynes/php-resque)
===========================================

php-resque (pronounced like "rescue") is a Redis-backed library for creating 
background jobs, placing those jobs on multiple queues, and processing them later.

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)

---

## Event/Hook System ##

php-resque has an events system that can be used by your application to change how some of 
the php-resque internals behave without needing to change the core code.

You listen in on events (listed below) by using the `Resque\Event::listen` method and supplying 
a callback that you would like triggered when the event is raised:

```php
Resque\Event::listen(Resque\Event::EVENT_NAME, [callback]);
```

`[callback]` may be anything in PHP that is callable by `call_user_func_array`:

* A string with the name of a function
* An array containing an object and method to call
* An array containing an object and a static method to call
* A Closure

Events may pass arguments, such as the worker or job, so your callback can accept these arguments.

You can stop listening to an event by calling `Resque\Event::forget` with the same arguments supplied to `Resque\Event::listen`.

It is up to your application to register event listeners. When enqueuing events in your application, 
it should be as easy as making sure php-resque is loaded and calling `Resque\Event::listen`.

When running workers, if you run workers via the default `bin/resque` script, your `include` script should 
initialise and register any listeners required for operation. If you have rolled your own worker manager, 
then it is again your responsibility to register listeners.

As an example, say you wanted to record how long each job took with your own logging software you could do something like this:

```php
Resque\Event::listen(Resque\Event::JOB_COMPLETE, function($event, $job) {
	myLoggerFunction($job->execTime());
});
```

The `Resque\Event::JOB_DONE` is triggered as last action in the job processing. While `Resque\Event::JOB_COMPLETE` is triggered after the jobs `perform()` method is finished, the `Resque\Event::JOB_DONE` Event is triggered after job output is stored in redis backend.
In short: `Resque\Event::JOB_COMPLETE` is fired, when the jobs perform method work is done, while `Resque\Event::JOB_DONE` is fired, when the whole job processing is coming to an end.

### Worker events ###

* `Resque\Event::WORKER_INSTANCE`       - New worker is created
* `Resque\Event::WORKER_STARTUP`        - Worker is starting up
* `Resque\Event::WORKER_SHUTDOWN`       - Worker is shutting down
* `Resque\Event::WORKER_FORCE_SHUTDOWN` - Worker is being forced to shutdown
* `Resque\Event::WORKER_REGISTER`       - Worker is registering itself
* `Resque\Event::WORKER_UNREGISTER`     - Worker is unregistering itself
* `Resque\Event::WORKER_WORK`           - Worker is working
* `Resque\Event::WORKER_FORK`           - Worker is about to fork
* `Resque\Event::WORKER_FORK_ERROR`     - There was an error forking
* `Resque\Event::WORKER_FORK_PARENT`    - After forking parent process
* `Resque\Event::WORKER_FORK_CHILD`     - After forking child process
* `Resque\Event::WORKER_WORKING_ON`     - Worker working on a job
* `Resque\Event::WORKER_DONE_WORKING`   - Worker finished working on a job
* `Resque\Event::WORKER_KILLCHILD`      - Worker kill child process
* `Resque\Event::WORKER_PAUSE`          - Pause worker
* `Resque\Event::WORKER_RESUME`         - Resume worker
* `Resque\Event::WORKER_WAKEUP`         - Wakeup worker
* `Resque\Event::WORKER_CLEANUP`        - Clean up worker
* `Resque\Event::WORKER_LOW_MEMORY`     - Low memory error
* `Resque\Event::WORKER_CORRUPT`        - Worker is corrupted

### Job events ###

* `Resque\Event::JOB_INSTANCE`       - New Job is created
* `Resque\Event::JOB_QUEUE`          - Job is about to be added to queue
* `Resque\Event::JOB_QUEUED`         - Job has been added to queue
* `Resque\Event::JOB_DELAY`          - Job is about to be delayed
* `Resque\Event::JOB_DELAYED`        - Job has been delayed
* `Resque\Event::JOB_QUEUE_DELAYED`  - Delayed job about to be queued
* `Resque\Event::JOB_QUEUED_DELAYED` - Delayed job has been queued
* `Resque\Event::JOB_PERFORM`        - Job is about to be run
* `Resque\Event::JOB_RUNNING`        - Job is running
* `Resque\Event::JOB_COMPLETE`       - Job has completed
* `Resque\Event::JOB_CANCELLED`      - Job has been cancelled
* `Resque\Event::JOB_FAILURE`        - Job has failed
* `Resque\Event::JOB_DONE`           - Job is done


---

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)