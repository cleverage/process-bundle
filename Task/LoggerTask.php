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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class LoggerTask
 *
 * Add custom log in state
 *
 * @package Task
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class LoggerTask extends AbstractConfigurableTask
{
    /** @var PropertyAccessorInterface */
    protected $accessor;

    /** @var NormalizerInterface */
    protected $normalizer;

    /**
     * @param PropertyAccessorInterface $accessor
     * @param NormalizerInterface       $normalizer
     * @internal param LoggerInterface $logger
     */
    public function __construct(PropertyAccessorInterface $accessor, NormalizerInterface $normalizer)
    {
        $this->accessor = $accessor;
        $this->normalizer = $normalizer;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\PropertyAccess\Exception\AccessException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     * @throws \Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException
     * @throws \Symfony\Component\Serializer\Exception\CircularReferenceException
     * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Serializer\Exception\LogicException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $context = [];
        foreach ($options['context'] as $contextInfo) {
            $value = $this->accessor->getValue($state, $contextInfo);
            if ($value instanceof \stdClass) {
                $context[$contextInfo] = json_decode(json_encode($value), true);
            } else {
                $context[$contextInfo] = $this->normalizer->normalize(
                    $value,
                    'json'
                );
            }
        }
        $state->log($options['message'], $options['level'], $options['reference'], $context);
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'level' => 'debug',
                'message' => 'Log state input',
                'context' => ['input'],
                'reference' => null,
            ]
        );
        $resolver->setAllowedTypes('level', ['string']);
        $resolver->setAllowedTypes('message', ['string']);
        $resolver->setAllowedTypes('context', ['array']);
        $resolver->setAllowedTypes('reference', ['string', 'NULL']);
    }

}