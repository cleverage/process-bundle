<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Filesystem;

/**
 * Provides common methods to file reading systems
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
trait FileHelperTrait
{
    /**
     * @param string $filePath
     * @param string $mode
     *
     * @return resource
     */
    protected function openResource(string $filePath, string $mode)
    {
        if (!\in_array($filePath, ['php://stdin', 'php://stdout', 'php://stderr'])) {
            $dirname = \dirname($filePath);
            if (!@mkdir($dirname, 0755, true) && !is_dir($dirname)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
            }
        }

        $resource = fopen($filePath, $mode);
        if (false === $resource) {
            throw new \UnexpectedValueException("Unable to open file: '{$filePath}' in {$mode} mode");
        }

        return $resource;
    }
}
