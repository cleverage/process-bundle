<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Get a property on the input and return it with PropertyAccessor
 *
 * Accepts an array or an object as an input and read a value from a property path.
 *
 * See [PropertyAccess Component Reference](https://symfony.com/doc/current/components/property_access.html) for details on property path syntax and behavior.
 *
 * ##### Task reference
 *
 *  * **Service**: `CleverAge\ProcessBundle\Task\PropertyGetterTask`
 *  * **Input**: `array` or `object` that can be accessed by the property accessor
 *  * **Output**: `any`, value of the property extracted from input.
 *
 * ##### Options
 *
 * * **`property`** (`string`, _required_): Property path to read from input
 *
 * @author Corentin Bouix <cbouix@clever-age.com>
 */
class PropertyGetterTask extends AbstractConfigurableTask
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @internal
     */
    public function __construct(LoggerInterface $logger, PropertyAccessorInterface $accessor)
    {
        $this->logger = $logger;
        $this->accessor = $accessor;
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $input = $state->getInput();
        $property = $options['property'];

        try {
            $output = $this->accessor->getValue($input, $property);
        } catch (\Exception $e) {
            $state->addErrorContextValue('property', $property);
            $state->setException($e);

            return;
        }

        $state->setOutput($output);
    }

    /**
     * {@inheritDoc}
     *
     * @internal
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'property',
            ]
        );
        $resolver->setAllowedTypes('property', ['string']);
    }
}
