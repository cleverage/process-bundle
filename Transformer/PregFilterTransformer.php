<?php

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PregFilterTransformer
 *
 * @package CleverAge\ProcessBundle\Transformer
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class PregFilterTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value
     *
     * @param mixed $value
     * @param array $options
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return mixed $value
     */
    public function transform($value, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $pattern = $options['pattern'];
        $replacement = $options['replacement'];

        return preg_filter($pattern, $replacement, $value);
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'preg_filter';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'pattern',
                'replacement',
            ]
        );
        $resolver->setAllowedTypes('pattern', ['string', 'array']);
        $resolver->setAllowedTypes('replacement', ['string', 'array']);
    }
}