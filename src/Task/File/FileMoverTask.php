<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Move the file passed as input, requires the destination path in options.
 */
class FileMoverTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $fs = new Filesystem();
        $file = $state->getInput();
        if (!$fs->exists($file)) {
            throw new \UnexpectedValueException("File does not exists: '{$file}'");
        }
        $dest = $options['destination'];
        if (is_dir($dest)) {
            $dest = rtrim((string) $dest, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.basename((string) $file);
        }
        if ($options['autoincrement']) {
            $dest = $this->makeFilenameUnique($dest);
        }
        $fs->rename($file, $dest, $options['overwrite']);
        $state->setOutput($dest);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['destination']);
        $resolver->setAllowedTypes('destination', ['string']);
        $resolver->setDefaults([
            'overwrite' => false,
            'autoincrement' => false,
        ]);
        $resolver->setAllowedTypes('overwrite', ['boolean']);
        $resolver->setAllowedTypes('autoincrement', ['boolean']);
    }

    protected function makeFilenameUnique(string $dest): string
    {
        $fs = new Filesystem();
        // Only the file name is changed, the directory part may contain dots
        $basename = basename($dest);
        $directory = substr($dest, 0, \strlen($dest) - \strlen($basename));
        // A leading dot (hidden file) is not an extension separator
        $dotPosition = strrpos($basename, '.');
        if (false === $dotPosition || 0 === $dotPosition) {
            $name = $basename;
            $extension = '';
        } else {
            $name = substr($basename, 0, $dotPosition);
            $extension = substr($basename, $dotPosition);
        }

        $uniqueDest = $dest;
        $i = 1;
        while ($fs->exists($uniqueDest)) {
            $uniqueDest = $directory.$name.'-'.$i.$extension;
            ++$i;
        }

        return $uniqueDest;
    }
}
