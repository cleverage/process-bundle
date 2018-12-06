<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Task\Database;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\PDOStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Fetch entities from doctrine
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DatabaseReaderTask extends AbstractConfigurableTask implements IterableTaskInterface, FinalizableTaskInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var PDOStatement */
    protected $statement;

    /** @var array|mixed */
    protected $nextItem;

    /**
     * @param LoggerInterface $logger
     * @param ManagerRegistry $doctrine
     */
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine)
    {
        $this->logger = $logger;
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
     * @throws \Doctrine\DBAL\DBALException
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
            $logContext = ['options' => $options];
            $this->logger->log($options['empty_log_level'], 'Empty resultset for query', $logContext);
            $state->setSkipped(true);

            return;
        }

        if (null !== $options['paginate']) {
            $results = [];
            $i = 0;
            while (false !== $result && $i++ < $options['paginate']) {
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
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
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
                'empty_log_level' => LogLevel::WARNING,
            ]
        );
        $resolver->setAllowedTypes('connection', ['NULL', 'string']);
        $resolver->setAllowedTypes('sql', ['NULL', 'string']);
        $resolver->setAllowedTypes('paginate', ['NULL', 'int']);
        $resolver->setAllowedTypes('limit', ['NULL', 'integer']);
        $resolver->setAllowedTypes('offset', ['NULL', 'integer']);
        $resolver->setAllowedValues(
            'empty_log_level',
            [
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::DEBUG,
                LogLevel::EMERGENCY,
                LogLevel::ERROR,
                LogLevel::INFO,
                LogLevel::NOTICE,
                LogLevel::WARNING,
            ]
        );
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */

        return $this->doctrine->getConnection($this->getOption($state, 'connection'));
    }
}
