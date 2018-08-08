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
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
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
    public const DEFAULT_TRANSFORMER = 'mapping';
    public const ACTIVE_TRANSFORMER = 'transformer';

    /** @var LoggerInterface */
    protected $logger;

    /** @var TransformerRegistry */
    protected $transformerRegistry;

    /** @var TransformerInterface */
    protected $transformer;

    /**
     * @param LoggerInterface     $logger
     * @param TransformerRegistry $transformerRegistry
     *
     */
    public function __construct(LoggerInterface $logger, TransformerRegistry $transformerRegistry)
    {
        $this->logger = $logger;
        $this->transformerRegistry = $transformerRegistry;
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
        $transformerOptions = $options;
        unset($transformerOptions[self::ACTIVE_TRANSFORMER]);

        try {
            $output = $this->transformer->transform(
                $state->getInput(),
                $this->getOptions($state)
            );
        } catch (\Exception $e) {
            $state->setError($state->getInput());
            $logContext = $state->getLogContext();
            if ($e->getPrevious()) {
                $logContext['error'] = $e->getPrevious()->getMessage();
            }
            $this->logger->error($e->getMessage(), $logContext);
            if ($state->getTaskConfiguration()->getErrorStrategy() === TaskConfiguration::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($state->getTaskConfiguration()->getErrorStrategy() === TaskConfiguration::STRATEGY_STOP) {
                $state->stop($e);
            }
        }
        $state->setOutput($output);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $transformerCodes = array_keys($this->transformerRegistry->getTransformers());
        $resolver->setDefault(static::ACTIVE_TRANSFORMER, static::DEFAULT_TRANSFORMER);
        $resolver->setAllowedValues(static::ACTIVE_TRANSFORMER, $transformerCodes);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     * @throws \UnexpectedValueException
     *
     * @return array
     */
    protected function getOptions(ProcessState $state)
    {
        if (null === $this->options) {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);

            $options = $state->getTaskConfiguration()->getOptions();
            if (!array_key_exists(static::ACTIVE_TRANSFORMER, $options)) {
                $options[static::ACTIVE_TRANSFORMER] = static::DEFAULT_TRANSFORMER;
            }

            $this->transformer = $this->transformerRegistry->getTransformer($options[static::ACTIVE_TRANSFORMER]);
            if ($this->transformer instanceof ConfigurableTransformerInterface) {
                $this->transformer->configureOptions($resolver);
            }

            $this->options = $resolver->resolve($state->getContextualizedOptions());
        }

        return $this->options;
    }
}
