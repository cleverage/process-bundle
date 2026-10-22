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

namespace CleverAge\ProcessBundle\Tests\Fixtures;

use CleverAge\ProcessBundle\Attribute\AsTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;

#[AsTransformer(code: 'app_attributed')]
final class AttributedTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $options = []): mixed
    {
        return $value;
    }

    public function getCode(): string
    {
        return 'app_attributed';
    }
}
