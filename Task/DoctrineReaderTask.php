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

use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LogLevel;
use Doctrine\ORM\Internal\Hydration\IterableResult;

/**
 * Fetch entities from doctrine
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DoctrineReaderTask extends AbstractDoctrineQueryTask implements IterableTaskInterface
{
    /** @var IterableResult */
    protected $iterator;

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
        if (!$this->iterator instanceof IterableResult) {
            throw new \LogicException('No iterator initialized');
        }
        $this->iterator->next();

        return $this->iterator->valid();
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        if (!$this->iterator) {
            $repository = $this->getManager($state)->getRepository($options['class_name']);
            $this->initIterator($repository, $options);
        }

        $result = $this->iterator->current();

        // Handle empty results
        if (false === $result) {
            $state->log('Empty resultset for query', LogLevel::WARNING, $options['class_name'], $options);
            $state->setStopped(true);

            return;
        }

        $state->setOutput($result[0]);
    }

    

    /**
     * @param EntityRepository $repository
     * @param array            $options
     *
     * @throws \UnexpectedValueException
     */
    protected function initIterator(EntityRepository $repository, array $options)
    {
        $qb = $this->getQueryBuilder(
            $repository,
            $options['criteria'],
            $options['order_by'],
            $options['limit'],
            $options['offset']
        );

        $this->iterator = $qb->getQuery()->iterate();
        $this->iterator->next(); // Move to first element
    }
}
