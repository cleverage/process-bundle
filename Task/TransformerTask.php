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

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Factory\InstancedTransformerFactory;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Transform an array of data based on mapping and sub-transformers
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class TransformerTask extends AbstractConfigurableTask
{
    use TransformerTrait;

    /** @var LoggerInterface */
    protected $logger;

    /** @var TransformerInterface */
    protected $transformer;

    /** @var InstancedTransformerFactory */
    protected $instancedTransformerFactory;

    /**
     * TransformerTask constructor.
     *
     * @param LoggerInterface             $logger
     * @param TransformerRegistry         $transformerRegistry
     * @param InstancedTransformerFactory $instancedTransformerFactory
     */
    public function __construct(LoggerInterface $logger, TransformerRegistry $transformerRegistry, InstancedTransformerFactory $instancedTransformerFactory)
    {
        $this->logger = $logger;
        $this->transformerRegistry = $transformerRegistry;
        $this->instancedTransformerFactory = $instancedTransformerFactory;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function execute(ProcessState $state)
    {
        $output = null;
        $options = $this->getOptions($state);

        try {
            $output = $this->applyTransformers($options['transformers'], $state->getInput());
        } catch (TransformerException $e) {
            $state->addErrorContextValue('error', $e->getPrevious()->getMessage());
            $state->setException($e);

            return;
        }
        $state->setOutput($output);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $this->configureTransformersOptions($resolver);
    }
}
