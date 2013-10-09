[php-resque](https://github.com/mjphaynes/php-resque)
===========================================

php-resque (pronounced like "rescue") is a Redis-backed library for creating 
background jobs, placing those jobs on multiple queues, and processing them later.

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)

---

## Logging ##

php-resque uses [Monolog](https://github.com/Seldaek/monolog) for logging which comes will built in support
for logging to various different files, databases and software.

There is inbuilt support for many of the Monolog drivers which make it very easy to start logging straight
from the command line when starting the worker. 

The following logging drivers are supported:

* `console`                              - Outputs everything to the command line
* `off`                                  - Doesn't output anything
* `stream:path/to/output.log`            - Appends to a file
* `path/to/output.log`                   - Appends to a file
* `errorlog:0`                           - Sends to php error_log
* `rotate:5:path/to/output.log`          - Saves to file and rotates files
* `redis://127.0.0.1:6379/log`           - Saves to Redis
* `mongodb://127.0.0.1:27017/dbname/log` - Saves to MongoDB
* `couchdb://127.0.0.1:27017/dbname`     - Saves to CouchDB
* `amqp://127.0.0.1:5763/name`           - Sends to AMQP server
* `socket:udp://127.0.0.1:80`            - Sends data to a socket
* `syslog:myfacility/local6`             - Sends data to syslog
* `cube:udp://localhost:5000`            - Sends data to Cube

The following parameters can be used and are replaced at runtime so it's easy to separate logs from
different workers and hosts:

* `%host%`   - Hostname
* `%worker%` - Worker ID
* `%pid%`    - Worker process ID
* `%date%`   - Current date (Y-m-d)
* `%time%`   - Current time (H:i)

An example of logging to Redis separating by worker, and to the terminal, might be:

```
$ bin/resque worker:start --log=redis://127.0.0.1:6379/%worker%:log --log=console
```


---

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)