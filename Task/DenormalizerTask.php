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

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalize input to output with configurable class and format
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DenormalizerTask extends AbstractConfigurableTask
{
    /** @var DenormalizerInterface */
    protected $denormalizer;

    /**
     * @param DenormalizerInterface $denormalizer
     */
    public function __construct(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        try {
            $normalizedData = $this->denormalizer->denormalize(
                $state->getInput(),
                $options['class'],
                $options['format'],
                $options['context']
            );
            $state->setOutput($normalizedData);
        } catch (\Exception $e) {
            $state->setError($state->getInput());
            if ($options[self::LOG_ERRORS]) {
                $state->log('Denormalizer exception: '.$e->getMessage(), LogLevel::ERROR);
            }
            if ($options[self::ERROR_STRATEGY] === self::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($options[self::ERROR_STRATEGY] === self::STRATEGY_STOP) {
                $state->stop($e);
            }
        }
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
        $resolver->setRequired(
            [
                'class',
            ]
        );
        $resolver->setAllowedTypes('class', ['string']);
        $resolver->setDefaults(
            [
                'format' => null,
                'context' => [],
            ]
        );
        $resolver->setAllowedTypes('format', ['NULL', 'string']);
        $resolver->setAllowedTypes('context', ['array']);
    }
}
