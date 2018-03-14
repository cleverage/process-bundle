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

use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Persists Doctrine entities
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DoctrineWriterTask extends AbstractDoctrineTask implements FinalizableTaskInterface
{
    /** @var array */
    protected $batch = [];

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function finalize(ProcessState $state)
    {
        $this->writeBatch($state);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function execute(ProcessState $state)
    {
        $entity = $state->getInput();

        $this->batch[] = $entity;
        if (count($this->batch) >= $this->getOption($state, 'batch_count')) {
            $this->writeBatch($state);
        }

        $state->setOutput($entity);
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
            'batch_count' => 1,
            'global_flush' => true,
            'clear_em' => true,
            'global_clear' => true,
        ]);
        $resolver->setAllowedTypes('batch_count', ['integer']);
        $resolver->setAllowedTypes('global_flush', ['boolean']);
        $resolver->setAllowedTypes('clear_em', ['boolean']);
        $resolver->setAllowedTypes('global_clear', ['boolean']);
        $resolver->setNormalizer('global_flush', function (Options $options, $value) {
            if ($options['batch_count'] > 1 && !$value) {
                throw new \UnexpectedValueException(
                    'Options batch_count and global_flush cannot be used simultaneously'
                );
            }

            return $value;
        });
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    protected function writeBatch(ProcessState $state)
    {
        if (count($this->batch) === 0) {
            return;
        }
        $options = $this->getOptions($state);

        $manager = $this->getManager($state);
        foreach ($this->batch as $entity) {
            $manager->persist($entity);

            if (!$options['global_flush']) {
                $manager->flush($entity);
            }
        }

        if ($options['global_flush']) {
            $manager->flush();
        }

        if (!$options['global_clear']) {
            foreach ($this->batch as $entity) {
                $manager->detach($entity);
            }
        }

        if ($options['clear_em'] && $options['global_clear']) {
            $manager->clear();
        }
        $this->batch = [];
    }
}
