<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Command',
        __DIR__ . '/Configuration',
        __DIR__ . '/Context',
        __DIR__ . '/DependencyInjection',
        __DIR__ . '/Event',
        __DIR__ . '/EventDispatcher',
        __DIR__ . '/EventListener',
        __DIR__ . '/Exception',
        __DIR__ . '/ExpressionLanguage',
        __DIR__ . '/Filesystem',
        __DIR__ . '/Logger',
        __DIR__ . '/Manager',
        __DIR__ . '/Model',
        __DIR__ . '/Registry',
        __DIR__ . '/Resources',
        __DIR__ . '/Task',
        __DIR__ . '/Tests',
        __DIR__ . '/Transformer',
        __DIR__ . '/Validator',
    ]);

    $rectorConfig->rules([
        ClassPropertyAssignToConstructorPromotionRector::class
    ]);

    /*$rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SymfonyLevelSetList::UP_TO_SYMFONY_54
    ]);*/
};
