<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\AbstractIterableOutputTask;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Exception\ParseException;
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
     * @throws UndefinedOptionsException
     * @throws AccessException
     * @throws \UnexpectedValueException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'file_path',
            ]
        );
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setNormalizer(
            'file_path',
            static function (Options $options, $value) {
                if (!file_exists($value)) {
                    throw new \UnexpectedValueException("File not found: {$value}");
                }

                return $value;
            }
        );
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws ParseException
     * @throws ExceptionInterface
     *
     * @return \Iterator
     */
    protected function initializeIterator(ProcessState $state): \Iterator
    {
        $filePath = $this->getOption($state, 'file_path');
        $content = Yaml::parseFile($filePath);
        if (!\is_array($content)) {
            throw new \InvalidArgumentException("File content is not an array: {$filePath}");
        }

        return new \ArrayIterator($content);
    }
}
