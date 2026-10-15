<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    // here we can define, what prepared sets of rules will be applied
    ->withComposerBased(phpunit: true, symfony: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, symfonyCodeQuality: true)
    ->withAttributesSets(symfony: true)
    ->withSets([
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])
;
