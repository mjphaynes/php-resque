# [php-resque](https://github.com/mjphaynes/php-resque)

php-resque (pronounced like "rescue") is a Redis-backed library for creating
background jobs, placing those jobs on multiple queues, and processing them later.

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)

---

## Configuration Options

Instead of having to pass through options as arguments all the time it is possible to use
a configuration file.

An example of this file with all possible options is [resque.yml](../resque.yml).
This file also contains descriptions of what each option does.

php-resque will try to find a file called `resque.yml` (or any other supported file extension) in and around the current working directory.
It does not matter if there is no configuration file.

Supported file extensions are: `yml`, `yaml`, `json`, and `php`.

You can set the configuration file path when running any command:

    $ vendor/bin/resque [command] -c /path/to/resque.yml

When adding jobs to the queue, you just can add the config file location as a parameter:

```php
use Resque\Config;

Config::loadConfig('my-custom-config.yml');

$payload = [
    'some' => 'data',
];

$job = Resque::push(
    TestJob::class,
    $payload,
    'default'
);
```

---

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)
