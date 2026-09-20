<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrStartsWithRector;
use Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrEndsWithRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/admin',
        __DIR__ . '/install',
        __DIR__ . '/api',
        __DIR__ . '/public/themes',
    ])
    ->withSkip([
        __DIR__ . '/lib/vendor/*',
        __DIR__ . '/lib/core/HTMLPurifier/*',
        __DIR__ . '/admin/assets/*',
        __DIR__ . '/public/themes/blog/assets/*',
    ])
    // Scoped downgrade rules matching the actual PHP 7.4 compat findings.
    // Full withDowngradeSets(php74: true) would also rewrite 42 files with
    // (string) casts on substr() — behavior-altering and unnecessary here,
    // since the codebase already parses and runs cleanly on PHP 7.4.
    ->withRules([
        DowngradeStrStartsWithRector::class,
        DowngradeStrEndsWithRector::class,
    ])
    ->withPhpVersion(PhpVersion::PHP_74);
