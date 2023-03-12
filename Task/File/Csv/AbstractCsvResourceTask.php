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

namespace CleverAge\ProcessBundle\Task\File\Csv;

use CleverAge\ProcessBundle\Filesystem\CsvResource;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generic abstract task to handle CSV resources
 */
abstract class AbstractCsvResourceTask extends AbstractConfigurableTask implements FinalizableTaskInterface
{
    /**
     * @var CsvResource
     */
    protected $csv;

    public function finalize(ProcessState $state): void
    {
        if ($this->csv instanceof CsvResource) {
            $this->csv->close();
        }
    }

    protected function initFile(ProcessState $state)
    {
        if ($this->csv) {
            return;
        }
        $options = $this->getOptions($state);
        $headers = $this->getHeaders($state, $options);
        $this->csv = new CsvResource(
            $state->getInput(),
            $options['delimiter'],
            $options['enclosure'],
            $options['escape'],
            $headers
        );
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'delimiter' => ';',
            'enclosure' => '"',
            'escape' => '\\',
            'headers' => null,
        ]);
        $resolver->setAllowedTypes('delimiter', ['string']);
        $resolver->setAllowedTypes('enclosure', ['string']);
        $resolver->setAllowedTypes('escape', ['string']);
        $resolver->setAllowedTypes('headers', ['null', 'array']);
    }

    /**
     * @return array
     */
    abstract protected function getHeaders(ProcessState $state, array $options);
}
