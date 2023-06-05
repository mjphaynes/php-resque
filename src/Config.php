<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque;

use InvalidArgumentException;
use Resque\Helpers\Util;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Resque configuration class.
 *
 * @package Resque
 * @author Paul Litovka <paxanddos@gmail.com>
 */
final class Config
{
    /**
     * Default config file name
     */
    public const DEFAULT_CONFIG_FILE = 'resque.php';

    /**
     * Supported config file extensions
     */
    public const SUPPORTED_CONFIG_EXT = ['php', 'json', 'yaml', 'yml'];

    /**
     * How long the job and worker data will remain in Redis for
     * after completion/shutdown in seconds. Default is one week.
     */
    public const DEFAULT_EXPIRY_TIME = 604800;

    /**
     * @var array Configuration settings array.
     */
    protected static $config = [];

    /**
     * Read and load data from a config file
     *
     * @param string $file The config file path
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function loadConfig(string $file = self::DEFAULT_CONFIG_FILE): void
    {
        [$path, $ext] = static::getConfigDetails($file);

        $config = [];

        switch ($ext) {
            case 'php':
                $config = static::fromPhp($path);
                break;
            case 'json':
                $config = static::fromJson($path);
                break;
            case 'yaml':
            case 'yml':
                $config = static::fromYaml($path);
                break;
            default:
                throw new RuntimeException("Could not load config file $file");
        }

        static::setConfig($config);
    }

    /**
     * Set the configuration array
     *
     * @param array<string, mixed> $config The configuration array
     */
    public static function setConfig(array $config): void
    {
        static::$config = $config;

        Redis::setConfig([
            'scheme'     => static::read('redis.scheme', Redis::DEFAULT_SCHEME),
            'host'       => static::read('redis.host', Redis::DEFAULT_HOST),
            'port'       => static::read('redis.port', Redis::DEFAULT_PORT),
            'namespace'  => static::read('redis.namespace', Redis::DEFAULT_NS),
            'password'   => static::read('redis.password', Redis::DEFAULT_PASSWORD),
            'rw_timeout' => static::read('redis.rw_timeout', Redis::DEFAULT_RW_TIMEOUT),
            'phpiredis'  => static::read('redis.phpiredis', Redis::DEFAULT_PHPIREDIS),
            'predis'     => static::read('predis'),
        ]);
    }

    /**
     * Gets a full path to a config file and its extension
     *
     * @throws \InvalidArgumentException
     *
     * @return string[]
     */
    protected static function getConfigDetails(string $file = self::DEFAULT_CONFIG_FILE): array
    {
        [$filename, $ext] = explode('.', basename($file));
        if (!in_array($ext, self::SUPPORTED_CONFIG_EXT)) {
            throw new InvalidArgumentException("The config file $file is not supported. Supported extensions are: ".implode(', ', self::SUPPORTED_CONFIG_EXT));
        }

        // Check if provided file is a valid path to a readable file
        if (realpath($file) && is_readable($file)) {
            return [realpath($file), $ext];
        }

        $baseDir = getcwd();
        $searchDirs = [
            $baseDir.'/',
            $baseDir.'/../',
            $baseDir.'/../../',
            $baseDir.'/config/',
            $baseDir.'/../config/',
            $baseDir.'/../../config/',
        ];

        $configFile = null;

        // Search for config file with any supported extension
        foreach ($searchDirs as $dir) {
            foreach (self::SUPPORTED_CONFIG_EXT as $supportedExt) {
                $path = "$dir$filename.$supportedExt";
                if (realpath($path) && is_readable($path)) {
                    $configFile = realpath($path);
                    $ext = $supportedExt;
                    break;
                }
            }
        }

        // If the config is not found, throw an exception
        if (!$configFile) {
            throw new InvalidArgumentException("The config file $file cannot be found or read.");
        }

        return [$configFile, $ext];
    }

    /**
     * Gets Resque config variable
     *
     * @param string $key     The key to search for (optional)
     * @param mixed  $default If key not found returns this (optional)
     *
     * @return mixed
     */
    public static function read(?string $key = null, $default = null)
    {
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
     * Parse a YAML config file and return the config array.
     *
     * @throws \RuntimeException
     */
    protected static function fromYaml(string $configFilePath): array
    {
        if (!class_exists(Yaml::class)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Missing yaml parser, symfony/yaml package is not installed.');
            // @codeCoverageIgnoreEnd
        }

        $configFile = file_get_contents($configFilePath);
        try {
            $configArray = Yaml::parse($configFile);
        } catch (ParseException $e) {
            throw new RuntimeException("File $configFilePath must be valid YAML: {$e->getMessage()}");
        }

        if (!is_array($configArray)) {
            throw new RuntimeException("File $configFilePath must be valid YAML");
        }

        return $configArray;
    }

    /**
     * Parse a JSON config file and return the config array.
     *
     * @throws \RuntimeException
     */
    protected static function fromJson(string $configFilePath): array
    {
        if (!function_exists('json_decode')) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Missing JSON parser, JSON extension is not installed.');
            // @codeCoverageIgnoreEnd
        }

        $configArray = json_decode(file_get_contents($configFilePath), true);
        if (!is_array($configArray)) {
            throw new RuntimeException("File $configFilePath must be valid JSON");
        }

        return $configArray;
    }

    /**
     * Parse a PHP config file and return the config array.
     *
     * @throws \RuntimeException
     */
    protected static function fromPhp(string $configFilePath): array
    {
        ob_start();
        $configArray = include $configFilePath;
        ob_end_clean();

        if (!is_array($configArray)) {
            throw new RuntimeException("File $configFilePath must return an array");
        }

        return $configArray;
    }
}
