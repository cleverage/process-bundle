<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Get a property on the input and return it with PropertyAccessor
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
     * @param LoggerInterface           $logger
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(LoggerInterface $logger, PropertyAccessorInterface $accessor)
    {
        $this->logger = $logger;
        $this->accessor = $accessor;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Exception
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
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
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
