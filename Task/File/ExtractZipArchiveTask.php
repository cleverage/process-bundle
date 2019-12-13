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
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ZipArchive;

/**
 * Class ExtractZipArchiveTask
 *
 * Extract the entirety or part of given Zip archive to given destination.
 */
class ExtractZipArchiveTask extends AbstractConfigurableTask
{
    /** @var string */
    const FILE_PATH_OPTION = 'file_path';

    /** @var string */
    const DESTINATION_OPTION = 'destination';

    /** @var string */
    const FILES_OPTION = 'files';

    /** @var string */
    const CHECK_DEST_OPTION = 'check_dest';

    /**
     * @param ProcessState $state
     *
     * @throws IOException
     * @throws ExceptionInterface
     * @throws \UnexpectedValueException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $fs = new Filesystem();
        $file = $options[self::FILE_PATH_OPTION];
        if (!$fs->exists($file)) {
            throw new \UnexpectedValueException("File does not exists: '{$file}'");
        }
        $dest = $options[self::DESTINATION_OPTION];
        if ($options[self::CHECK_DEST_OPTION] && !is_dir($dest)) {
            throw new \UnexpectedValueException("Destination must be a directory: '{$dest}'");
        }

        $zip = new ZipArchive();
        if ($zip->open($file) === true) {
            if (!$zip->extractTo($dest, $options[self::FILES_OPTION])) {
                throw new \UnexpectedValueException("Unable to extract archive '{$file}' to destination '{$dest}'");
            }
            $zip->close();
        } else {
            throw new \UnexpectedValueException("Unable to open archive: '{$file}'");
        }
        $state->setOutput($dest);
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
                self::FILE_PATH_OPTION,
                self::DESTINATION_OPTION,
            ]
        );
        $resolver->setAllowedTypes(self::FILE_PATH_OPTION, ['string']);
        $resolver->setAllowedTypes(self::DESTINATION_OPTION, ['string']);

        $resolver->setDefault(self::FILES_OPTION, null);
        $resolver->setAllowedTypes(self::FILES_OPTION, ['array', 'null']);

        $resolver->setDefault(self::CHECK_DEST_OPTION, false);
        $resolver->setAllowedTypes(self::CHECK_DEST_OPTION, ['bool']);
    }
}
