<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/lib',
        __DIR__ . '/admin',
        __DIR__ . '/public/themes',
        __DIR__ . '/install',
        __DIR__ . '/api'
    ])
    ->exclude('vendor')
    ->exclude('assets')
    ->notName('*.min.css')
    ->notName('*.min.js')
    ->files();

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(false);
