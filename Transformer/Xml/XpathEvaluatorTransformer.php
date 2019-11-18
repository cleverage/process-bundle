<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer\Xml;

use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Manipulate XML elements using xpath
 */
class XpathEvaluatorTransformer implements ConfigurableTransformerInterface
{

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('query');
        $resolver->setAllowedTypes('query', ['string', 'array']);

        $resolver->setDefault('single_result', true);
        $resolver->setAllowedTypes('single_result', 'bool');

        $resolver->setDefault('ignore_missing', true);
        $resolver->setAllowedTypes('ignore_missing', 'bool');

        $resolver->setDefault('unwrap_value', true);
        $resolver->setAllowedTypes('unwrap_value', 'bool');
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value, array $options = [])
    {
        if (!$value instanceof \DOMNode) {
            throw new \UnexpectedValueException("Input should be a " . \DOMNode::class);
        }

        $xpath = $this->buildXpath($value);

        $query = $options['query'];
        if (\is_array($query)) {
            $result = \array_map(function ($subquery) use ($xpath, $value, $options) {
                return $this->query($xpath, $subquery, $value, $options);
            }, $query);
        } else {
            $result = $this->query($xpath, $query, $value, $options);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return 'xpath_evaluator';
    }

    /**
     * @param \DOMNode $node
     *
     * @return \DOMXPath
     */
    public function buildXpath(\DOMNode $node): \DOMXPath
    {
        $doc = $node instanceof \DOMDocument ? $node : $node->ownerDocument;

        return new \DOMXPath($doc);
    }

    /**
     * @param \DOMXPath $xpath
     * @param string    $query
     * @param \DOMNode  $node
     * @param array     $options
     *
     * @return mixed
     */
    public function query(\DOMXPath $xpath, string $query, \DOMNode $node, array $options)
    {
        // TODO check if query is relative ?
        $nodeList = $xpath->query($query, $node);
        $results = iterator_to_array($nodeList);

        // Convert results to text
        if ($options['unwrap_value']) {
            $results = \array_map(function (\DOMNode $item) use ($query, $options) {
                if ($item instanceof \DOMAttr) {
                    return $item->value;
                }

                if ($item instanceof \DOMText) {
                    // If you have an error, remember that you may need to use the "text()" xpath selector
                    return $item->textContent;
                }

                throw new \UnexpectedValueException("Xpath result cannot be unwrapped for query '{$query}'");
            }, $results);
        }

        // Unwrap the node list
        if ($options['single_result']) {
            if (count($results) > 1) {
                throw new \UnexpectedValueException("There is too much results for query '{$query}'");
            }

            if (count($results) === 0 && !$options['ignore_missing']) {
                throw new \UnexpectedValueException("There is not enough results for query '{$query}'");
            }

            if(count($results) === 1) {
                $results = $results[0];
            } else {
                $results = null;
            }

        }

        return $results;
    }

}
