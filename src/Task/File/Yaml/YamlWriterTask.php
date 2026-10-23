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

namespace CleverAge\ProcessBundle\Task\File\Yaml;

use CleverAge\ProcessBundle\Exception\RuntimeException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

/**
 * Writes a YAML file from an array.
 *
 * @final since 5.4
 */
class YamlWriterTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $filePath = $options['file_path'];
        // Same as the other writers: the parent directory is created if needed, for a local path only (not for a stream
        // wrapper such as compress.zlib://, s3:// or php://stdout)
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $filePath) || str_starts_with(strtolower($filePath), 'file://')) {
            $dirname = \dirname($filePath);
            if (!@mkdir($dirname, 0o755, true) && !is_dir($dirname)) {
                throw new RuntimeException(\sprintf('Directory "%s" was not created', $dirname));
            }
        }
        // The PHP warning is replaced by the exception below, also outside of the debug mode
        if (false === @file_put_contents($filePath, Yaml::dump($state->getInput(), $options['inline']))) {
            throw new RuntimeException(\sprintf('Unable to write file "%s": %s', $filePath, error_get_last()['message'] ?? 'unknown error'));
        }
        $state->setOutput($filePath);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['file_path']);
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setDefaults([
            'inline' => 4,
        ]);
        $resolver->setAllowedTypes('inline', ['integer']);
    }
}
