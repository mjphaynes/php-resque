<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$header = <<<EOF
This file is part of the php-resque package.

(c) Michael Haynes <mike@mjphaynes.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$finder = PhpCsFixer\Finder::create()
    ->exclude('docker')
    ->exclude('docs')
    ->exclude('examples')
    ->exclude('vendor')
    ->in(__DIR__);

return (new PhpCsFixer\Config())->setRules([
        '@PSR2' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'phpdoc_types' => true,
        'phpdoc_scalar' => true,
        'phpdoc_align' => true,
        'array_syntax' => ['syntax' => 'short'],
        'header_comment' => ['header' => $header],
    ])
    ->setFinder($finder);