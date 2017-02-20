<?php
/**
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Sami\Sami;
use Symfony\Component\Finder\Finder;
use Sami\Version\GitVersionCollection;

define('RESQUE_DIR', realpath(__DIR__ . '/../../'));

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in($dir = RESQUE_DIR . '/src')
;

$versions = GitVersionCollection::create($dir)
    // ->addFromTags(function ($version) { return preg_match('/^v?1\.\d+\.\d+$/', $version); })
    // ->addFromTags('v1.0.*')
    // ->add('1.0', '1.0 branch')
    ->add('master', 'master branch')
;

return new Sami($iterator, array(
    'theme'                => 'enhanced',
    'title'                => 'php-resque',
    'versions'             => $versions,
    'build_dir'            => RESQUE_DIR.'/docs/api/%version%',
    'cache_dir'            => RESQUE_DIR.'/docs/api/%version%/.cache',
    'simulate_namespaces'  => false,
    'include_parent_data'  => true,
    'default_opened_level' => 1,
));
