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

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

/**
 * Writes a YAML file from an array
 */
class YamlWriterTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        file_put_contents($options['file_path'], Yaml::dump($state->getInput(), $options['inline']));
        $state->setOutput($options['file_path']);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['file_path']);
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setDefaults([
            'inline' => 4,
        ]);
        $resolver->setAllowedTypes('inline', ['integer']);
    }
}
