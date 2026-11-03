<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Configuration;

/**
 * Before 5.7, the dashes of the process, task and generic transformer codes were replaced by underscores by the Symfony
 * configuration (key normalization), unless the code also contained an underscore: "demo.x-y" was registered as
 * "demo.x_y". The codes are now kept as written; their former name is a deprecated alias, until v6.0.
 *
 * @internal
 */
final class DeprecatedCodeAliases
{
    /**
     * @param iterable<int|string> $codes
     *
     * @return array<string, string> former name => code
     */
    public static function create(iterable $codes): array
    {
        $codes = array_map(strval(...), [...$codes]);
        $aliases = [];
        foreach ($codes as $code) {
            if (!str_contains($code, '-') || str_contains($code, '_')) {
                continue;
            }
            $alias = str_replace('-', '_', $code);
            // Both were defined: none was renamed
            if (!\in_array($alias, $codes, true)) {
                $aliases[$alias] = $code;
            }
        }

        return $aliases;
    }
}
