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


use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstancedTransformer implements ConfigurableTransformerInterface
{
    /** @var string */
    protected $code;

    /** @var TransformerInterface */
    protected $transformer;

    /** @var array */
    protected $options;

    /** @var ContextualOptionResolver */
    protected $contextualOptionResolver;

    /**
     * InstancedTransformer constructor.
     *
     * @param string                   $code
     * @param TransformerInterface     $transformer
     * @param array                    $options
     * @param ContextualOptionResolver $contextualOptionResolver
     */
    public function __construct(string $code, TransformerInterface $transformer, array $options, ContextualOptionResolver $contextualOptionResolver)
    {
        $this->code = $code;
        $this->transformer = $transformer;
        $this->options = $options;
        $this->contextualOptionResolver = $contextualOptionResolver;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // TODO: Implement configureOptions() method.
        // $contextualizedOptions = $this->contextualOptionResolver->contextualizeOptions($this->options, $options);
    }

    public function transform($value, array $options = [])
    {
        return $this->transformer->transform($value, $this->options);
    }

    public function getCode()
    {
        //TODO predefined or calculated ?
        return $this->code;
    }


}
