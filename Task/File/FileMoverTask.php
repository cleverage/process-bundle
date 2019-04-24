<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
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
 * Move the file passed as input, requires the destination path in options
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class FileMoverTask extends AbstractConfigurableTask
{
    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $fs = new Filesystem();
        $file = $state->getInput();
        if (!$fs->exists($file)) {
            throw new \UnexpectedValueException("File does not exists: '{$file}'");
        }
        $dest = $options['destination'];
        if (is_dir($dest)) {
            $dest = rtrim($dest, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.basename($file);
        }
        if ($options['autoincrement']) {
            $dest = $this->makeFilenameUnique($dest);
        }
        $fs->rename($file, $dest, $options['overwrite']);
        $state->setOutput($dest);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'destination',
            ]
        );
        $resolver->setAllowedTypes('destination', ['string']);
        $resolver->setDefaults(
            [
                'overwrite' => false,
                'autoincrement' => false,
            ]
        );
        $resolver->setAllowedTypes('overwrite', ['boolean']);
        $resolver->setAllowedTypes('autoincrement', ['boolean']);
    }

    /**
     * @param string $dest
     *
     * @return string
     */
    protected function makeFilenameUnique($dest)
    {
        $fs = new Filesystem();
        $i = 1;
        while ($fs->exists($dest)) {
            if (preg_match('/^(.*?)(-\d+)?(\.[^\.]*)$/', $dest, $matches)) {
                $dest = $matches[1].'-'.$i.$matches[3];
                ++$i;
            } else {
                $dest .= '-'.$i; // Fallback brutal mode
            }
        }

        return $dest;
    }
}
