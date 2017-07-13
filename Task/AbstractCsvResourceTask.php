<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Filesystem\CsvResource;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
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
        parent::configureOptions($resolver);
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
