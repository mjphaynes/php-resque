<?php


declare(strict_types=1);

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use InvalidArgumentException;
use Resque\Config;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest
 */
final class ConfigTest extends TestCase
{
    /**
     * @var string
     */
    private $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/php-resque-config-'.uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') as $file) {
            unlink($file);
        }

        rmdir($this->directory);
        Config::setConfig([]);
    }

    public function testItLoadsAPhpConfigFile(): void
    {
        $file = $this->writeConfig('resque.php');

        $this->assertSame(['redis' => ['scheme' => 'tcp']], Config::loadConfig($file));
    }

    /**
     * The extension used to be taken from the second segment of the file name,
     * which rejected every name carrying more than one dot.
     */
    public function testItLoadsAConfigFileWhoseNameContainsADot(): void
    {
        $file = $this->writeConfig('my.resque.php');

        $this->assertSame(['redis' => ['scheme' => 'tcp']], Config::loadConfig($file));
    }

    /**
     * @dataProvider unsupportedFileProvider
     */
    public function testItRejectsAFileItCannotParse(string $name): void
    {
        $file = $this->writeConfig($name);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not supported');

        Config::loadConfig($file);
    }

    public function unsupportedFileProvider(): array
    {
        return [
            'unknown extension' => ['resque.txt'],
            'no extension' => ['resque'],
            'dot but no extension' => ['resque.'],
        ];
    }

    private function writeConfig(string $name): string
    {
        $file = $this->directory.'/'.$name;
        file_put_contents($file, "<?php return ['redis' => ['scheme' => 'tcp']];\n");

        return $file;
    }
}
