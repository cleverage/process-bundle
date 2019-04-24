<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\NoSuchOptionException;
use Symfony\Component\OptionsResolver\Exception\OptionDefinitionException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EvaluatorTransformer
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class EvaluatorTransformer implements ConfigurableTransformerInterface
{

    /** @var ExpressionLanguage */
    protected $language;

    /**
     * EvaluatorTransformer constructor.
     */
    public function __construct()
    {
        $this->language = new ExpressionLanguage();
    }


    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // Allow to cache the parsing by statically defining variables
        $resolver->setDefault('variables', null);
        $resolver->addAllowedTypes('variables', ['null', 'array']);

        $resolver->setRequired(
            [
                'expression',
            ]
        );
        $resolver->setAllowedTypes('expression', ['string', ParsedExpression::class]);
        $resolver->setNormalizer(
            'expression',
            function (Options $options, $expression) {
                if (is_array($options['variables'])) {
                    return $this->language->parse($expression, $options['variables']);
                }

                return $expression;
            }
        );
    }

    /**
     * @param mixed $value
     * @param array $options
     *
     * @throws UndefinedOptionsException
     * @throws OptionDefinitionException
     * @throws NoSuchOptionException
     * @throws MissingOptionsException
     * @throws InvalidOptionsException
     * @throws AccessException
     *
     * @return string
     */
    public function transform($value, array $options = [])
    {
        return $this->language->evaluate(
            $options['expression'],
            $value
        );
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return 'evaluator';
    }
}
