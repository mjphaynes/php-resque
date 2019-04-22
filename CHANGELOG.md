# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [2.2.0] - 2019-04-22
- Make signals configurable by event callback (PR [#85](https://github.com/mjphaynes/php-resque/pull/85))
- Provide predis native configuration (PR [#88](https://github.com/mjphaynes/php-resque/pull/88))
- Add travis support and CS check (PR [#72](https://github.com/mjphaynes/php-resque/pull/72))
- Use pcntl_async_signals if available (PR [#65](https://github.com/mjphaynes/php-resque/pull/65))
- Fix cli_set_process_title error on macOS (PR [#92](https://github.com/mjphaynes/php-resque/pull/92))

## [2.1.2] - 2017-09-19
- Fix job processing of the last queue (Issue [#61](https://github.com/mjphaynes/php-resque/issues/61))

## [2.1.1] - 2017-08-31
- Fix "undefined index" notice (Issue [#59](https://github.com/mjphaynes/php-resque/issues/59))

## [2.1.0] - 2017-08-31
- Add JOB_DONE event (PR [#58](https://github.com/mjphaynes/php-resque/pull/58))
- Allow remote shutdown of workers (PR [#50](https://github.com/mjphaynes/php-resque/pull/50))
- Improve documentation

## [2.0.0] - 2017-03-01
- Update required Symfony components to 2.7+ or 3.x
- Update required Predis version to 1.1.x
- Change worker wait log level from INFO to DEBUG (Commit [4915d51](https://github.com/mjphaynes/php-resque/commit/4915d51ca2593a743cecbab9597ad6a1314bdbed))
- Add option to allow phpiredis support (Commit [4e22e0fb](https://github.com/mjphaynes/php-resque/commit/4e22e0fb31d8658c2a1ef73a5a44c927fd88d55c))
- Add option to set Redis read/write timeout (PR [#27](https://github.com/mjphaynes/php-resque/pull/27))
- Change code style to PSR-2 (PR [#25](https://github.com/mjphaynes/php-resque/pull/25))
- Fix closures with whitespace in their declaration (Issue [#30](https://github.com/mjphaynes/php-resque/issues/30))
- Fix job stability by reconnecting to redis after forking (Commit [cadfb09e](https://github.com/mjphaynes/php-resque/commit/cadfb09e81152cf902ef7f20e6883d29e6d1373b))
- Fix crash if the status is not set (Commit [cadfb09e](https://github.com/mjphaynes/php-resque/commit/cadfb09e81152cf902ef7f20e6883d29e6d1373b))
- Improve code style to increase PSR-2 compliance (Commit [36daf9a](https://github.com/mjphaynes/php-resque/commit/36daf9a23128e75eab15522ecc595ece8e4b6874))
- Add this changelog!

## [1.3.0] - 2016-01-22
- Remove optional proctitle extension dependency (PR #18)

## [1.2.4] - 2015-04-17
- Monolog line break fix

## [1.2.3] - 2015-04-02
- Monolog composer fix

## [1.2.2] - 2014-11-05
- Dev dependencies bug fix

## [1.2.1] - 2014-11-05
- Dependencies bug fix

## [1.2.0] - 2014-11-05
- Updated symfony dependencies

## [1.1.3] - 2014-06-23
- ob_clean fix

## [1.1.2] - 2014-06-23
- Config file error fix

## [1.1.1] - 2014-06-23
- Autoload directory fix

## [1.1.0] - 2014-02-18
- Bump lib versions for Monolog & Symfony

## 1.0.0 - 2013-10-09
- First public release of php-resque

[Unreleased]: https://github.com/mjphaynes/php-resque/compare/2.2.0...HEAD
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
