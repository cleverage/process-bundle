<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
