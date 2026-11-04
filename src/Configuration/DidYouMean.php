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
 * Suggestion of the closest known code in an error message: " Did you mean "x"?", or an empty string.
 *
 * @internal
 */
final class DidYouMean
{
    /**
     * @param iterable<int|string> $candidates
     */
    public static function suggest(string $value, iterable $candidates): string
    {
        $best = null;
        $bestDistance = null;
        foreach ($candidates as $candidate) {
            $candidate = (string) $candidate;
            $distance = levenshtein(strtolower($value), strtolower($candidate));
            // A typo, not another code: at most a third of the length (at least 1, at most 3 changes)
            if ($distance > max(1, min(3, intdiv(\strlen($value), 3)))) {
                continue;
            }
            if (null === $bestDistance || $distance < $bestDistance) {
                $best = $candidate;
                $bestDistance = $distance;
            }
        }

        return null === $best ? '' : \sprintf(' Did you mean "%s"?', $best);
    }
}
