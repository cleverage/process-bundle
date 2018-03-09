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

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads a YAML file and iterate over its root elements
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class YamlReaderTask extends AbstractIterableOutputTask
{
    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \UnexpectedValueException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(
            [
                'file_path',
            ]
        );
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setNormalizer(
            'file_path',
            function (Options $options, $value) {
                if (!file_exists($value)) {
                    throw new \UnexpectedValueException('File not found: '.$value);
                }

                return $value;
            }
        );
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return \Iterator
     */
    protected function initializeIterator(ProcessState $state): \Iterator
    {
        $filePath = $this->getOption($state, 'file_path');
        $content = Yaml::parseFile($filePath);
        if (!\is_array($content)) {
            throw new \InvalidArgumentException('File content is not an array: '.$filePath);
        }

        return new \ArrayIterator($content);
    }
}
