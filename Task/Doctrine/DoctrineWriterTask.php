<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Task\Doctrine;

use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Persists Doctrine entities
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DoctrineWriterTask extends AbstractDoctrineTask implements FlushableTaskInterface
{
    /** @var array */
    protected $batch = [];

    /**
     * @param ProcessState $state
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function flush(ProcessState $state)
    {
        $state->setSkipped(true);
        $this->writeBatch($state);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     * @throws \UnexpectedValueException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function execute(ProcessState $state)
    {
        $entity = $state->getInput();
        $this->batch[] = $entity;
        if (\count($this->batch) >= $this->getOption($state, 'batch_count')) {
            $this->writeBatch($state);
        }

        $state->setOutput($entity);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'batch_count' => 1,
                'global_flush' => true,
                'clear_em' => false,
                'global_clear' => true,
            ]
        );
        $resolver->setAllowedTypes('batch_count', ['integer']);
        $resolver->setAllowedTypes('global_flush', ['boolean']);
        $resolver->setAllowedTypes('clear_em', ['boolean']);
        $resolver->setAllowedTypes('global_clear', ['boolean']);
        $resolver->setNormalizer(
            'batch_count',
            function (Options $options, $batchCount) {
                if (1 !== $batchCount) {
                    throw new InvalidConfigurationException('batch_count must be set to 1');
                }

                return $batchCount;
            }
        );
    }

    /**
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     * @throws \Doctrine\ORM\ORMException
     */
    protected function writeBatch(ProcessState $state)
    {
        if (0 === \count($this->batch)) {
            return;
        }
        $options = $this->getOptions($state);

        $entityManagers = new \SplObjectStorage();
        foreach ($this->batch as $entity) {
            $class = ClassUtils::getClass($entity);
            $entityManager = $this->doctrine->getManagerForClass($class);
            if (!$entityManager instanceof EntityManagerInterface) {
                throw new \UnexpectedValueException("No manager found for class {$class}");
            }
            $entityManager->persist($entity);

            if (!$options['global_flush']) {
                if (!$entityManager instanceof EntityManager) {
                    throw new \UnexpectedValueException("Manager for class {$class} does not support unitary flush");
                }
                $entityManager->flush($entity);
            }
            $entityManagers->attach($entityManager);
        }

        if ($options['global_flush']) {
            foreach ($entityManagers as $entityManager) {
                $entityManager->flush();
            }
        }

        if ($options['clear_em']) {
            foreach ($entityManagers as $entityManager) {
                if ($options['global_clear']) {
                    $entityManager->clear();
                } else {
                    foreach ($this->batch as $entity) {
                        $entityManager->detach($entity);
                    }
                }
            }
        }
        $this->batch = [];
    }
}
