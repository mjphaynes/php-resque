[php-resque](https://github.com/mjphaynes/php-resque)
===========================================

php-resque (pronounced like "rescue") is a Redis-backed library for creating 
background jobs, placing those jobs on multiple queues, and processing them later.

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)

---

## Configuration Options ##

Instead of having to pass through options as arguments all the time it is possible to use
a configuration file.

An example of this file with all possible options is [config.yml](https://github.com/mjphaynes/php-resque/blob/master/config.yml).
This file also contains descriptions of what each option does.

php-resque will try to find a file called `config.yml` in and around the current working directory.
It does not matter if there is no configuration file.

You can set the configuration file path when running any command:

```
$ bin/resque [command] -c /path/to/config.yml
```

When adding jobs to the queue, you just can add the config file location as parameter:

```php
\Resque::loadConfig('my-custom-config.yml');

$payload = [
    'some' => 'data',
];

$job = \Resque::push(
    '\MyApplication\Jobs\TestJob', // your custom job class 
    $payload,                      // data available in job
    'default'                      // queue name
);
```

---

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)
