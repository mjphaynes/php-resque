<?php
/**
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Resque\Redis;
use Resque\Helpers\Util;
use Symfony\Component\Yaml;

/**
 * Main Resque class
 *
 * @author Michael Haynes <mike@mjphaynes.com>
 */
class Resque {

	/**
	 * php-resque version
	 */
	const VERSION = '1.0.0';

	/**
	 * How long the job and worker data will remain in Redis for
	 * after completion/shutdown in seconds. Default is one week.
	 */
	const DEFAULT_EXPIRY_TIME = 604800;

	/**
	 * Default config file name
	 */
	const DEFAULT_CONFIG_FILE = 'config.yml';

	/**
	 * @var array Configuration settings array.
	 */
	protected static $config = array();

	/**
	 * @var \Resque\Queue The queue instance.
	 */
	protected static $queue = null;

	/**
	 * Create a queue instance.
	 *
	 * @return \Resque\Queue
	 */
	public static function queue() {
		if (!static::$queue) {
			static::$queue = new Resque\Queue();
		}

		return static::$queue;
	}

	/**
	 * Dynamically pass calls to the default connection.
	 *
	 * @param  string $method     The method to call
	 * @param  array  $parameters The parameters to pass
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters) {
		$callable = array(static::queue(), $method);

		return call_user_func_array($callable, $parameters);
	}

	/**
	 * Reads and loads data from a config file
	 *
	 * @param  string  $file The config file path
	 * @return bool
	 */
	public static function loadConfig($file = self::DEFAULT_CONFIG_FILE) {
		self::readConfigFile($file);

		Redis::setConfig(array(
			'scheme'    => static::getConfig('redis.scheme', Redis::DEFAULT_SCHEME),
			'host'      => static::getConfig('redis.host', Redis::DEFAULT_HOST),
			'port'      => static::getConfig('redis.port', Redis::DEFAULT_PORT),
			'namespace' => static::getConfig('redis.namespace', Redis::DEFAULT_NS),
			'password'  => static::getConfig('redis.password', Redis::DEFAULT_PASSWORD),
			'read_write_timeout'  => static::getConfig('redis.read_write_timeout', Redis::DEFAULT_RW_TIMEOUT),
		));

		return true;
	}

	/**
	 * Reads data from a config file
	 *
	 * @param  string  $file The config file path
	 * @return array
	 */
	public static function readConfigFile($file = self::DEFAULT_CONFIG_FILE) {
		if (!is_string($file)) {
			throw new InvalidArgumentException('The config file path must be a string, type passed "'.gettype($file).'".');
		}

		$baseDir = realpath(dirname($file));
		$searchDirs = array($baseDir.'/', $baseDir.'/../', $baseDir.'/../../', $baseDir.'/config/', $baseDir.'/../config/', $baseDir.'/../../config/');

		$filename = basename($file);

		$configFile = null;
		foreach ($searchDirs as $dir) {
			if (realpath($dir.$filename) and is_readable($dir.$filename)) {
				$configFile = realpath($dir.$filename);
				break;
			}
		}

		if (is_null($configFile) and $file !== self::DEFAULT_CONFIG_FILE) {
			throw new InvalidArgumentException('The config file "'.$file.'" cannot be found or read.');
		}

		if (!$configFile) {
			return static::$config;
		}

		// Try to parse the contents
		try {
			$yaml = Yaml\Yaml::parse(file_get_contents($configFile));

		} catch (Yaml\Exception\ParseException $e) {
			throw new Exception('Unable to parse the config file: '.$e->getMessage());
		}

		return static::$config = $yaml;
	}

	/**
	 * Gets Resque config variable
	 *
	 * @param  string  $key     The key to search for (optional)
	 * @param  mixed   $default If key not found returns this (optional)
	 * @return mixed
	 */
	public static function getConfig($key = null, $default = null) {
		if (!is_null($key)) {
			if (false !== Util::path(static::$config, $key, $found)) {
				return $found;
			} else {
				return $default;
			}
		}

		return static::$config;
	}

	/**
	 * Gets Resque stats
	 *
	 * @return array
	 */
	public static function stats() {
		return Redis::instance()->hgetall('stats');
	}

}