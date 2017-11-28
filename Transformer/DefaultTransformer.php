<?php
namespace CleverAge\ProcessBundle\Transformer;


use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultTransformer implements ConfigurableTransformerInterface
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('value');
    }

    public function transform($value, array $options = [])
    {
        if(!$value) {
            return $options['value'];
        }

        return $value;
    }

    public function getCode()
    {
        return 'default';
    }

}
