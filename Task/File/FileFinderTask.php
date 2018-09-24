<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;
use \Iterator;

/**
 * Class FileFinderTask
 *
 * List files with Symfony Finder component
 *
 * @author  Fabien Salles <fsalles@clever-age.com>
 */
class FileFinderTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    /** @var Iterator  */
    protected $iterator;

    /**
     * @param ProcessState $state
     */
    public function initialize(ProcessState $state)
    {
        // Configure options
        parent::initialize($state);

        $finder = (new Finder())->in($this->getOption($state, 'source_directory'));

        if ($this->getOption($state, 'file_pattern')) {
            $finder->name($this->getOption($state, 'file_pattern'));
        }

        // Because $finder->getIterator() not working in a same way
        $this->iterator = new \ArrayIterator(iterator_to_array($finder, false));
    }

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        if ($this->iterator->valid()) {
            /** @var SplFileInfo $file */
            $file = $this->iterator->current();
            $state->setOutput($file->getRealPath());
        } else {
            $state->setSkipped(true);
        }
    }

    /**
     * @param ProcessState $state
     *
     * @return bool|mixed
     */
    public function next(ProcessState $state)
    {
        $this->iterator->next();

        return $this->iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['source_directory']);
        $resolver->setAllowedTypes('source_directory', 'string');

        $resolver->setDefault('file_pattern', null);
        $resolver->setAllowedTypes('file_pattern', ['string', 'null']);
    }
}