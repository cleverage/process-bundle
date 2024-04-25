<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Validator;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;

class ConstraintLoader extends AbstractLoader
{
    public function loadClassMetadata(ClassMetadata $metadata): bool
    {
        return false;
    }

    /**
     * Build constraints from textual data.
     *
     * @see \Symfony\Component\Validator\Mapping\Loader\YamlFileLoader::parseNodes
     */
    public function buildConstraints(array $nodes): array
    {
        $values = [];

        foreach ($nodes as $name => $childNodes) {
            if (is_numeric($name) && \is_array($childNodes) && 1 === \count($childNodes)) {
                $options = current($childNodes);

                if (\is_array($options)) {
                    $options = $this->buildConstraints($options);
                }

                $values[] = $this->newConstraint(key($childNodes), $options);
            } else {
                if (\is_array($childNodes)) {
                    $childNodes = $this->buildConstraints($childNodes);
                }

                $values[$name] = $childNodes;
            }
        }

        return $values;
    }
}
