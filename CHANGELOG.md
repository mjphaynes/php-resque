# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [4.0.0] - 2023-03-31

### Added

-   Support for PHP and JSON configuration files

-   Extendable console application class

-   Job blueprint class as a base for all jobs

-   Jobs' `setUp` and `tearDown` methods now receive the `Resque\Job` instance as an argument

-   Docker environment for development

-   More type declarations for method arguments and return types

-   Suggestions for `ext-mongodb` extension and `mongodb/mongodb` package

-   Suggestion for `symfony/yaml` package, it's now optional

### Changed

-   Minimum PHP version is now `7.2`. Supported PHP versions are `^7.2 || ^8.0`

-   Default configuration file should have the name `resque` instead of `config` (e.g. `resque.php`)

-   Refactored structure to follow PSR-4 autoloading standard (used to follow PSR-0)

-   Main `Resque` class is now located under the `Resque` namespace (e.g. `Resque\Resque::push()`)

-   Improved deployment. Now only mandatory files are included in the dist package

-   Improved code intelligence by adding PHP DocBlocks comments to the main `Resque` class

-   Worker will log on a DEBUG level when no queues found (instead of INFO)

-   Bumped `monolog/monolog` dependency to `^2.5`

-   Bumped `predis/predis` dependency to `^2.1`

-   Bumped `symfony/console`, `symfony/process`, and `symfony/yaml` dependencies to `^5.4|^6.0`

-   Bumped `phpunit/phpunit` dependency to `^8.0|^9.0`

-   Several code style improvements

### Deprecated

-   `phpiredis` configuration option. This extension is not maintained anymore and will be removed

-   `Cube` logger connector. This logger is not maintained anymore and will be removed

### Removed

-   `Sami` documentation generator

### Fixed

-   Passing a command as a string when creating a Process instance in the `SpeedTest` command

-   Custom configuration file not being loaded in the `SpeedTest` command

-   MongoDB logger connector using removed classes

-   Incorrect return type in commands

-   `SeializableClosure` deprecation notice

-   Various Logger usage errors

-   Incorrect type declarations causing errors in PHP 8

-   Incorrect type used in the `ConsoleProcessor` class

-   PHP deprecation notice in the `Util` class

### Security

-   Added `final` keyword to classes that should not be extended

## [3.1.1] - 2023-01-31

-   Improve PHP8 compatibility

## [3.1.0] - 2023-01-04

-   Allow canceling delayed jobs (PR [#99](https://github.com/mjphaynes/php-resque/pull/99))
-   Remove the proctitle extension suggestion, as it is not needed on PHP7+

## [3.0.0] - 2023-01-03

-   Reduce dependency coupling (PR [#94](https://github.com/mjphaynes/php-resque/pull/94))
-   Clean up code
-   Support Symfony components v4 and v5
-   Bump minimum PHP version to 7.1

## [2.2.0] - 2019-04-22

-   Make signals configurable by event callback (PR [#85](https://github.com/mjphaynes/php-resque/pull/85))
-   Provide predis native configuration (PR [#88](https://github.com/mjphaynes/php-resque/pull/88))
-   Add Travis support and CS check (PR [#72](https://github.com/mjphaynes/php-resque/pull/72))
-   Use pcntl_async_signals if available (PR [#65](https://github.com/mjphaynes/php-resque/pull/65))
-   Fix cli_set_process_title error on macOS (PR [#92](https://github.com/mjphaynes/php-resque/pull/92))

## [2.1.2] - 2017-09-19

-   Fix job processing of the last queue (Issue [#61](https://github.com/mjphaynes/php-resque/issues/61))

## [2.1.1] - 2017-08-31

-   Fix "undefined index" notice (Issue [#59](https://github.com/mjphaynes/php-resque/issues/59))

## [2.1.0] - 2017-08-31

-   Add JOB_DONE event (PR [#58](https://github.com/mjphaynes/php-resque/pull/58))
-   Allow remote shutdown of workers (PR [#50](https://github.com/mjphaynes/php-resque/pull/50))
-   Improve documentation

## [2.0.0] - 2017-03-01

-   Update required Symfony components to 2.7+ or 3.x
-   Update required Predis version to 1.1.x
-   Change worker wait for log level from INFO to DEBUG (Commit [4915d51](https://github.com/mjphaynes/php-resque/commit/4915d51ca2593a743cecbab9597ad6a1314bdbed))
-   Add option to allow phpiredis support (Commit [4e22e0fb](https://github.com/mjphaynes/php-resque/commit/4e22e0fb31d8658c2a1ef73a5a44c927fd88d55c))
-   Add option to set Redis to read/write timeout (PR [#27](https://github.com/mjphaynes/php-resque/pull/27))
-   Change code style to PSR-2 (PR [#25](https://github.com/mjphaynes/php-resque/pull/25))
-   Fix closures with whitespace in their declaration (Issue [#30](https://github.com/mjphaynes/php-resque/issues/30))
-   Fix job stability by reconnecting to redis after forking (Commit [cadfb09e](https://github.com/mjphaynes/php-resque/commit/cadfb09e81152cf902ef7f20e6883d29e6d1373b))
-   Fix crash if the status is not set (Commit [cadfb09e](https://github.com/mjphaynes/php-resque/commit/cadfb09e81152cf902ef7f20e6883d29e6d1373b))
-   Improve code style to increase PSR-2 compliance (Commit [36daf9a](https://github.com/mjphaynes/php-resque/commit/36daf9a23128e75eab15522ecc595ece8e4b6874))
-   Add this changelog!

## [1.3.0] - 2016-01-22

-   Remove optional proctitle extension dependency (PR #18)

## [1.2.4] - 2015-04-17

-   Monolog line break-fix

## [1.2.3] - 2015-04-02

-   Monolog composer fix

## [1.2.2] - 2014-11-05

-   Dev dependencies bug fix

## [1.2.1] - 2014-11-05

-   Dependencies bug fix

## [1.2.0] - 2014-11-05

-   Updated symfony dependencies

## [1.1.3] - 2014-06-23

-   ob_clean fix

## [1.1.2] - 2014-06-23

-   Config file error fix

## [1.1.1] - 2014-06-23

-   Autoload directory fix

## [1.1.0] - 2014-02-18

-   Bump lib versions for Monolog & Symfony

## 1.0.0 - 2013-10-09

-   First public release of php-resque

[unreleased]: https://github.com/mjphaynes/php-resque/compare/4.0.0...HEAD
[4.0.0]: https://github.com/mjphaynes/php-resque/compare/3.1.1...4.0.0
[3.1.1]: https://github.com/mjphaynes/php-resque/compare/3.1.0...3.1.1
[3.1.0]: https://github.com/mjphaynes/php-resque/compare/3.0.0...3.1.0
[3.0.0]: https://github.com/mjphaynes/php-resque/compare/2.2.0...3.0.0
[2.2.0]: https://github.com/mjphaynes/php-resque/compare/2.1.2...2.2.0
[2.1.2]: https://github.com/mjphaynes/php-resque/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/mjphaynes/php-resque/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/mjphaynes/php-resque/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/mjphaynes/php-resque/compare/1.3.0...2.0.0
[1.3.0]: https://github.com/mjphaynes/php-resque/compare/1.2.4...1.3.0
[1.2.4]: https://github.com/mjphaynes/php-resque/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/mjphaynes/php-resque/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/mjphaynes/php-resque/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/mjphaynes/php-resque/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/mjphaynes/php-resque/compare/1.1.3...1.2.0
[1.1.3]: https://github.com/mjphaynes/php-resque/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/mjphaynes/php-resque/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/mjphaynes/php-resque/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/mjphaynes/php-resque/compare/1.0.0...1.1.0
