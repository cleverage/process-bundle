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

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException;
use CleverAge\ProcessBundle\Exception\MissingProcessException;

/**
 * Gives the process configurations (implemented by ProcessConfigurationRegistry, autowired by this interface).
 */
interface ProcessConfigurationRegistryInterface
{
    /**
     * @throws MissingProcessException              when the process does not exist
     * @throws InvalidProcessConfigurationException when the process configuration is not valid
     */
    public function getProcessConfiguration(string $processCode): ProcessConfiguration;

    /**
     * The processes whose configuration is not valid are left out (ProcessConfigurationRegistry logs an error), they
     * do not prevent listing the other ones.
     *
     * @return array<string, ProcessConfiguration> indexed by process code
     */
    public function getProcessConfigurations(): array;

    public function hasProcessConfiguration(string $processCode): bool;
}
