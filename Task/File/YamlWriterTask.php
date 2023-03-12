<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

/**
 * Writes a YAML file from an array
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class YamlWriterTask extends AbstractConfigurableTask
{
    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @throws IOException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        file_put_contents($options['file_path'], Yaml::dump($state->getInput(), $options['inline']));
        $state->setOutput($options['file_path']);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'file_path',
            ]
        );
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setDefaults(
            [
                'inline' => 4,
            ]
        );
        $resolver->setAllowedTypes('inline', ['integer']);
    }
}
