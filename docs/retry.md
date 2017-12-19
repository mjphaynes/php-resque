[php-resque](https://github.com/mjphaynes/php-resque)
===========================================

php-resque (pronounced like "rescue") is a Redis-backed library for creating 
background jobs, placing those jobs on multiple queues, and processing them later.

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)

---

## Retry ##

php-resque can retry jobs. The inbuilt support works at two level :

- At worker level, the worker can be configured and, if forced, retry jobs
- At Job level, a `RetryExceptionInterface` is thrown and the worker process it

The following argument can be added to the Worker :

- `-r`, `--retry` with one of the following value : `constant`/`polynomial`/`exponential`/... (default: `constant`)
- `-a`, `--max-attemps` an integer value to set the max attenmpt of Job (default : 3)
- `-f`, `--force-retry` an boolean to force jobs to retry following worker rules (default : false)

If `--retry` is set and if a job don't specify the retry strategy, then fallback to this one
If `--force-retry` is set, **every job** will be retried following the rule set.


### Example

Sample Job that can be retried if an `ExpectedException` is thrown:

```php
use Resque\Exception\Retry;

class JobWithRetry {

    function process($args) {
        try {
            throw new ExpectedException('That is expected');
        } catch (ExpectedException $e) {
            throw new Retry($e);
        }
    }
}
```


---

[← Go back to main documentation](https://github.com/mjphaynes/php-resque)