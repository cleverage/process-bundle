<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Detach Doctrine entities from unit of work
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DoctrineDetacherTask extends AbstractDoctrineTask
{
    /**
     * @param ProcessState $state
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function execute(ProcessState $state)
    {
        $entity = $state->getInput();
        $manager = $this->getManager($state);
        $manager->detach($entity);
        if ($this->getOption($state, 'global_clear')) {
            $manager->clear();
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'global_clear' => false,
        ]);
        $resolver->setAllowedTypes('global_clear', ['boolean']);
    }
}
