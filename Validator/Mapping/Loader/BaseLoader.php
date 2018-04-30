<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Validator\Mapping\Loader;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MappingException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;

/**
 * Custom loader to manually call constraints on values, using YML-like config
 *
 * @TODO   find a way to properly refactor this with sidus/eav-model-bundle
 *
 * @see    \Sidus\EAVModelBundle\Validator\Mapping\Loader\BaseLoader for base inspiration
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class BaseLoader extends YamlFileLoader
{
    /** @noinspection PhpMissingParentConstructorInspection
     *
     * Overriding file loader constructor
     * There is no need for a file
     */
    public function __construct()
    {
    }

    /**
     * Loads validation metadata into a {@link ClassMetadata} instance.
     *
     * @param ClassMetadata $metadata The metadata to load
     *
     * @return bool Whether the loader succeeded
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        return false; // throw an exception ?
    }

    /**
     * @param array $constraints
     *
     * @return Constraint[]
     * @throws MappingException
     */
    public function loadCustomConstraints(array $constraints)
    {
        return $this->parseNodes($constraints);
    }
}
