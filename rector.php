<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_85)
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets(php82: true)
    // here we can define, what prepared sets of rules will be applied
    ->withComposerBased(phpunit: true, symfony: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, symfonyCodeQuality: true)
    ->withAttributesSets(symfony: true)
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])
;
