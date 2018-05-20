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
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Doctrine\Common\Persistence\ManagerRegistry;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\DBAL\Driver\PDOStatement;

/**
 * Fetch entities from doctrine
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DatabaseReaderTask extends AbstractConfigurableTask implements IterableTaskInterface, FinalizableTaskInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var PDOStatement */
    protected $statement;

    /** @var array|mixed */
    protected $nextItem;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration
     *
     * @param ProcessState $state
     *
     * @throws \LogicException
     *
     * @return bool
     */
    public function next(ProcessState $state)
    {
        if (!$this->statement instanceof PDOStatement) {
            throw new \LogicException('No iterator initialized');
        }

        $this->nextItem = $this->statement->fetch();

        return (bool) $this->nextItem;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        if (!$this->statement) {
            $this->statement = $this->initializeStatement($state);
        }

        // Check if the next item has been stored by the next() call
        if (null !== $this->nextItem) {
            $result = $this->nextItem;
            $this->nextItem = null;
        } else {
            $result = $this->statement->fetch();
        }

        // Handle empty results
        if (false === $result) {
            $state->log('Empty resultset for query', LogLevel::WARNING, $options['table'], $options);
            $state->setStopped(true);

            return;
        }

        if (null !== $options['paginate']) {
            $results = [];
            $i = 0;
            while ($result !== false && $i++ < $options['paginate']) {
                $results[] = $result;
                $result = $this->statement->fetch();
            }
            $state->setOutput($results);
        } else {
            $state->setOutput($result);
        }
    }

    /**
     * @param ProcessState $state
     */
    public function finalize(ProcessState $state)
    {
        if ($this->statement) {
            $this->statement->closeCursor();
        }
    }

    /**
     * @param ProcessState $state
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    protected function initializeStatement(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $connection = $this->getConnection($state);
        $sql = $options['sql'];

        if (null === $sql) {
            $qb = $connection->createQueryBuilder();
            $qb
                ->select('tbl.*')
                ->from($options['table'], 'tbl');

            if ($options['limit']) {
                $qb->setMaxResults($options['limit']);
            }
            if ($options['offset']) {
                $qb->setFirstResult($options['offset']);
            }

            $sql = $qb->getSQL();
        }

        return $connection->executeQuery($sql);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'table',
            ]
        );
        $resolver->setAllowedTypes('table', ['string']);
        $resolver->setDefaults(
            [
                'connection' => null,
                'sql' => null,
                'limit' => null,
                'offset' => null,
                'paginate' => null,
            ]
        );
        $resolver->setAllowedTypes('connection', ['NULL', 'string']);
        $resolver->setAllowedTypes('sql', ['NULL', 'string']);
        $resolver->setAllowedTypes('paginate', ['NULL', 'int']);
        $resolver->setAllowedTypes('limit', ['NULL', 'integer']);
        $resolver->setAllowedTypes('offset', ['NULL', 'integer']);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection(ProcessState $state)
    {
        return $this->doctrine->getConnection($this->getOption($state, 'connection'));
    }
}
