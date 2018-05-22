<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use CleverAge\ProcessBundle\Transformer\TransformerRegistryAwareInterface;

/**
 * Holds all tagged transformer services
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class TransformerRegistry
{
    /** @var TransformerInterface[] */
    protected $transformers;

    /**
     * @param TransformerInterface $transformer
     */
    public function addTransformer(TransformerInterface $transformer)
    {
        if ($transformer instanceof TransformerRegistryAwareInterface) {
            $transformer->setTransformerRegistry($this);
        }
        $this->transformers[$transformer->getCode()] = $transformer;
    }

    /**
     * @return TransformerInterface[]
     */
    public function getTransformers()
    {
        return $this->transformers;
    }

    /**
     * @param string $code
     *
     * @throws MissingTransformerException
     *
     * @return TransformerInterface
     */
    public function getTransformer($code)
    {
        if (!$this->hasTransformer($code)) {
            throw new MissingTransformerException($code);
        }

        return $this->transformers[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasTransformer($code)
    {
        return array_key_exists($code, $this->transformers);
    }
}
