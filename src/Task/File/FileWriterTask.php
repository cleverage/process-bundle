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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileWriterTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);

        $fs = new Filesystem();
        $fs->dumpFile($options['filename'], $state->getInput());

        $state->setOutput($options['filename']);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['filename']);
        $resolver->setAllowedTypes('filename', ['string']);
    }
}
