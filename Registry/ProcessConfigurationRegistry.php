<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Exception\MissingProcessException;

/**
 * Build and holds all the process configurations
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessConfigurationRegistry
{
    /** @var ProcessConfiguration[] */
    protected $processConfigurations = [];

    /**
     * @param array $rawConfiguration
     */
    public function __construct(array $rawConfiguration)
    {
        foreach ($rawConfiguration as $processCode => $rawProcessConfiguration) {
            $taskConfigurations = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($rawProcessConfiguration['tasks'] as $taskCode => $rawTaskConfiguration) {
                $taskConfigurations[$taskCode] = new TaskConfiguration(
                    $taskCode,
                    $rawTaskConfiguration['service'],
                    $rawTaskConfiguration['options'],
                    $rawTaskConfiguration['outputs'],
                    $rawTaskConfiguration['errors']
                );
            }

            $this->processConfigurations[$processCode] = new ProcessConfiguration(
                $processCode,
                $taskConfigurations,
                $rawProcessConfiguration['options'],
                $rawProcessConfiguration['entry_point']
            );
        }
    }

    /**
     * @param string $processCode
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingProcessException
     *
     * @return ProcessConfiguration
     */
    public function getProcessConfiguration(string $processCode): ProcessConfiguration
    {
        if (!$this->hasProcessConfiguration($processCode)) {
            throw new MissingProcessException($processCode);
        }

        return $this->processConfigurations[$processCode];
    }

    /**
     * @return ProcessConfiguration[]
     */
    public function getProcessConfigurations(): array
    {
        return $this->processConfigurations;
    }

    /**
     * @param string $processCode
     *
     * @return bool
     */
    public function hasProcessConfiguration(string $processCode): bool
    {
        return array_key_exists($processCode, $this->processConfigurations);
    }
}
