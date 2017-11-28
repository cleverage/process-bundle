<?php
namespace CleverAge\ProcessBundle\Transformer;


use Symfony\Component\OptionsResolver\OptionsResolver;

class DateFormatTransformer implements ConfigurableTransformerInterface
{
    public function transform($value, array $options = [])
    {
        if(!$value) {
            return $value;
        }
        $date = new \DateTime($value);

        return $date->format($options['format']);
    }

    public function getCode()
    {
        return 'date_format';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('format');
        $resolver->setAllowedTypes('format', 'string');
    }
}
