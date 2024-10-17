<?php

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!file_exists(__DIR__.'/src')) {
    exit(0);
}

$fileHeaderComment = <<<'EOF'
    This file is part of the CleverAge/ProcessBundle package.

    Copyright (c) Clever-Age

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    EOF;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PHP71Migration' => true,
        '@PHP82Migration' => true,
        '@PHPUnit75Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'protected_to_private' => false,
        'native_constant_invocation' => ['strict' => false],
        'header_comment' => ['header' => $fileHeaderComment],
        'modernize_strpos' => true,
        'get_class_to_class_keyword' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
            ->append([__FILE__])
    )
    ->setCacheFile('.php-cs-fixer.cache')
;
