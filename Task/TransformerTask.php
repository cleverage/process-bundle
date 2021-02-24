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

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\NoSuchOptionException;
use Symfony\Component\OptionsResolver\Exception\OptionDefinitionException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
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
     * @throws ExceptionInterface
     * @throws MissingTransformerException
     * @throws \UnexpectedValueException
     * @throws AccessException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     * @throws NoSuchOptionException
     * @throws OptionDefinitionException
     * @throws UndefinedOptionsException
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
     * @throws UndefinedOptionsException
     * @throws OptionDefinitionException
     * @throws NoSuchOptionException
     * @throws MissingOptionsException
     * @throws InvalidOptionsException
     * @throws AccessException
     * @throws MissingTransformerException
     * @throws ExceptionInterface
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $this->configureTransformersOptions($resolver);
    }
}
