<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Factory;


use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use CleverAge\ProcessBundle\Transformer\InstancedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstancedTransformerFactory
{
    /** @var string */
    protected $className;

    /** @var ContextualOptionResolver */
    protected $contextualOptionResolver;

    /** @var TransformerRegistry */
    protected $transformerRegistry;

    /**
     * InstancedTransformerFactory constructor.
     *
     * @param string                   $className
     * @param ContextualOptionResolver $contextualOptionResolver
     * @param TransformerRegistry      $transformerRegistry
     */
    public function __construct(string $className, ContextualOptionResolver $contextualOptionResolver, TransformerRegistry $transformerRegistry)
    {
        $this->className = $className;
        $this->contextualOptionResolver = $contextualOptionResolver;
        $this->transformerRegistry = $transformerRegistry;
    }


    /**
     * @param $code
     * @param $options
     *
     * @return \CleverAge\ProcessBundle\Transformer\TransformerInterface
     */
    public function create($code, $options)
    {
        $transformer = $this->transformerRegistry->getTransformer($code);
        if ($transformer instanceof ConfigurableTransformerInterface) {
            $transformerOptionsResolver = new OptionsResolver();
            $transformer->configureOptions($transformerOptionsResolver);
            $options = $transformerOptionsResolver->resolve($options);
        }

        $instancedTransformer = new InstancedTransformer('instanced_'.$code, $transformer, $options, $this->contextualOptionResolver);

        return $instancedTransformer;
    }
}
