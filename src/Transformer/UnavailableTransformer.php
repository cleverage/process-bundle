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

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Exception\LogicException;

/**
 * Replaces a transformer whose dependencies are not available (e.g. the Symfony serializer is not enabled), so that
 * the container still compiles and the transformer code is still known: it fails only when it is used, with an
 * explicit message.
 *
 * @internal
 *
 * @see \CleverAge\ProcessBundle\DependencyInjection\Compiler\UnavailableServicesCompilerPass
 */
final readonly class UnavailableTransformer implements TransformerInterface
{
    public function __construct(
        private string $code,
        private string $message,
    ) {
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        throw new LogicException($this->message);
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
