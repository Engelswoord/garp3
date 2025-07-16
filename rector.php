<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/application',
        __DIR__ . '/library',
        __DIR__ . '/packages',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php84: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withPhpVersion(Rector\ValueObject\PhpVersion::PHP_84)
    ->withRules([
        CompleteDynamicPropertiesRector::class,
    ]);
