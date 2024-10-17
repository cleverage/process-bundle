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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows a service to configure the options for a transformer before running the transform function.
 */
interface ConfigurableTransformerInterface extends TransformerInterface
{
    public function configureOptions(OptionsResolver $resolver): void;
}
