<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Transform an array of data based on mapping and sub-transformers.
 */
class TransformerTask extends AbstractConfigurableTask
{
    use TransformerTrait;

    protected ?TransformerInterface $transformer = null;

    public function __construct(
        protected LoggerInterface $logger,
        TransformerRegistry $transformerRegistry
    ) {
        $this->transformerRegistry = $transformerRegistry;
    }

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);

        try {
            $output = $this->applyTransformers($options['transformers'], $state->getInput());
        } catch (TransformerException $e) {
            $state->addErrorContextValue('error', $e->getPrevious()?->getMessage());
            $state->setException($e);

            return;
        }
        $state->setOutput($output);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureTransformersOptions($resolver);
    }
}
