<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
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
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
abstract class AbstractCsvResourceTask extends AbstractConfigurableTask implements FinalizableTaskInterface
{
    /** @var CsvResource */
    protected $csv;

    /**
     * @param ProcessState $state
     */
    public function finalize(ProcessState $state)
    {
        if ($this->csv instanceof CsvResource) {
            $this->csv->close();
        }
    }

    /**
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \RuntimeException
     */
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

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'delimiter' => ';',
                'enclosure' => '"',
                'escape' => '\\',
                'headers' => null,
            ]
        );
        $resolver->setAllowedTypes('delimiter', ['string']);
        $resolver->setAllowedTypes('enclosure', ['string']);
        $resolver->setAllowedTypes('escape', ['string']);
        $resolver->setAllowedTypes('headers', ['NULL', 'array']);
    }

    /**
     * @param ProcessState $state
     * @param array        $options
     *
     * @return array
     */
    abstract protected function getHeaders(ProcessState $state, array $options);
}
