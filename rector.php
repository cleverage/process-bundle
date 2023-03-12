<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonyLevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->parallel();
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

    $rectorConfig->paths([__DIR__]);

    $rectorConfig->skip([__DIR__ . '/vendor']);

    $rectorConfig->sets([
        SetList::TYPE_DECLARATION,
        LevelSetList::UP_TO_PHP_81,
        SymfonyLevelSetList::UP_TO_SYMFONY_54,
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_81);
};
