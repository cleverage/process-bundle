<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Registry\TransformerRegistry;

/**
 * Allows a transformer to holds the transformer registry, would'nt be possible otherwise due to a circular reference
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
interface TransformerRegistryAwareInterface
{
    /**
     * @param TransformerRegistry $transformerRegistry
     */
    public function setTransformerRegistry(TransformerRegistry $transformerRegistry);
}
