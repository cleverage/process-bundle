<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\FinalizableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Doctrine\Bundle\DoctrineBundle\Registry;
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
    /** @var Registry */
    protected $doctrine;

    /** @var PDOStatement */
    protected $statement;

    /** @var array|mixed */
    protected $nextItem;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
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
     * @return bool
     * @throws \LogicException
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
            $connection = $this->getConnection($state);
            $sql = $options['sql'];
            if (null === $sql) {
                $qb = $connection->createQueryBuilder();
                $qb
                    ->select('tbl.*')
                    ->from($options['table'], 'tbl');
                $sql = $qb->getSQL();
            }
            $this->statement = $connection->executeQuery($sql);
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
            $state->log('Empty resultset for query', LogLevel::WARNING, $options['class_name'], $options);
            $state->setStopped(true);

            return;
        }

        $state->setOutput($result);
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
            ]
        );
        $resolver->setAllowedTypes('connection', ['NULL', 'string']);
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
