[php-resque](https://github.com/mjphaynes/php-resque)
===

php-resque (pronounced like "rescue") is a Redis-backed library for creating
background jobs, placing those jobs on multiple queues, and processing them later.

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)

---

## Commands & Options ##

### Commands ###

To run a command navigate to your project root and run `bin/resque` with a command, e.g.:

```
$ bin/resque worker:start
```

php-resque comes with the following commands:

* `worker:start`    -  Polls for jobs on specified queues and executes job when found
* `worker:stop`     -  Stop a running worker. If no worker id set then stops all workers
* `worker:cancel`   -  Cancel job on a running worker. If no worker id set then cancels all workers
* `worker:pause`    -  Pause a running worker. If no worker id set then pauses all workers
* `worker:resume`   -  Resume a running worker. If no worker id set then resumes all workers
* `worker:restart`  -  Restart a running worker. If no worker id set then restarts all workers
* `job:queue`       -  Queue a new job to run with optional delay
* `hosts`           -  List all running workers on host
* `list`            -  Lists commands
* `queues`          -  Get queue statistics
* `workers`         -  List all running workers on host
* `cleanup`         -  Cleans up php-resque data, removing dead hosts, workers and jobs
* `clear`           -  Clears all php-resque data from Redis
* `help`            -  Displays help for a command
* `socket:connect`  -  Connects to a php-resque receiver socket
* `socket:receive`  -  Listens to socket in order to receive events
* `socket:send`     -  Sends a command to a php-resque receiver socket
* `speed:test`      -  Performs a speed test on php-resque to see how many jobs/second it can compute


### Options ###

To specify a configuration option use the following syntax:

```
$ bin/resque command --option=value
```

There are some options that can be used for any command:

* `config`    - Path to config file. Inline options override.
* `include`   - Path to include php file.
* `host`      - The Redis hostname.
* `port`      - The Redis port.
* `scheme`    - The Redis scheme to use.
* `namespace` - The Redis namespace to use. This is prefixed to all keys.
* `password`  - The Redis AUTH password
* `log`       - Specify the handler(s) to use for logging.
* `events`    - Outputs all events to the console, for debugging.

And here are the command specific options:

* `worker:start`
    * `queue`          - The queue(s) to listen on, comma separated.
    * `blocking`       - Use Redis pop blocking or time interval.
    * `interval`       - Blocking timeout/interval speed in seconds.
    * `timeout`        - Seconds a job may run before timing out.
    * `memory`         - The memory limit in megabytes.
    * `pid`            - Absolute path to PID file, must be writeable by worker.
* `worker:stop`
    * `id`             - The id of the worker to stop (optional; if not present stops all workers).
    * `force`          - Force worker to stop, cancelling any current job.
* `worker:cancel`
    * `id`             - The id of the worker to cancel it's running job (optional; if not present cancels all workers).
* `worker:pause`
    * `id`             - The id of the worker to pause (optional; if not present pauses all workers).
* `worker:resume`
    * `id`             - The id of the worker to resume (optional; if not present resumes all workers).
* `worker:restart`
    * `id`             - The id of the worker to restart (optional; if not present restarts all workers).
* `job:queue`
    * `job`            - The job to run.
    * `args`           - The arguments to send with the job.
    * `queue`          - The queue to add the job to.
    * `delay`          - The amount of time or a unix time to delay execution of job till.
* `clear`
    * `force`          - Force without asking.
* `socket:connect`
    * `connecthost`    - The host to connect to.
    * `connectport`    - The port to connect to.
    * `connecttimeout` - The connection timeout time (seconds).
* `socket:receive`
    * `listenhost`     - The host to listen on.
    * `listenport`     - The port to listen on.
    * `listenretry`    - If can't bind address or port then retry every <timeout> seconds until it can.
    * `listentimeout`  - The retry timeout time (seconds).
* `socket:send`
    * `cmd`            - The command to send to the receiver.
    * `id`             - The id of the worker (optional; required for worker: commands).
    * `connecthost`    - The host to send to.
    * `connectport`    - The port to send on.
    * `connecttimeout` - The send request timeout time (seconds).
    * `force`          - Force the command.
    * `json`           - Whether to return the response in JSON format.


---

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)