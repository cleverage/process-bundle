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

namespace CleverAge\ProcessBundle\Command;

use CleverAge\ProcessBundle\Entity\ProcessHistory;
use CleverAge\ProcessBundle\Entity\TaskHistory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanLogCommand
 *
 * Clean log table
 *
 * Exemple :
 * - `--process-filters="processCode:like:internal%"` : remove all log of process with code starts with "internal"
 * - `--task-filters="level:=:debug" --task-filters="loggedAt:<:- 1 week"` : remove all debug task histories older than
 * 1 week
 * - `--process-filters="processCode:like:internal%" --task-filters="level:=:debug"` : remove debug task histories of
 * process with code starts with "internal"
 *
 * @package CleverAge\ProcessBundle\Command
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class CleanLogCommand extends ContainerAwareCommand
{
    const VALID_COMPARISON = ['=', '!=', '>', '<', '>=', '<=', 'in', 'not in', 'like', 'is null', 'is not null'];
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \LogicException
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('cleverage:process:clean-log');
        $this->addOption(
            'process-filters',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Filter on process attributs with format %field%:%comparaison-operator%:%value%'
        );
        $this->addOption(
            'task-filters',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Filter on task attributs with format %field%:%comparaison-operator%:%value%'
        );
        $this->addOption(
            'remove-orphan-process',
            null,
            InputOption::VALUE_NONE,
            'Clean orphan process history items'
        );
    }

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('fire', new OutputFormatterStyle('red'));

        try {
            $processFilters = $this->extractFilterOptions(
                $input->getOption('process-filters'),
                ProcessHistory::class
            );
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Failed to parse process filters : '.$e->getMessage());
        }
        try {
            $taskFilters = $this->extractFilterOptions(
                $input->getOption('task-filters'),
                TaskHistory::class
            );
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Failed to parse task filters : '.$e->getMessage());
        }

        if ($processFilters && $taskFilters) {
            $result = $this
                ->cleanTaskHistoryWithProcessFilters(
                    $processFilters,
                    $taskFilters
                );
            $output->writeln(sprintf('Task history : %d filtred item(s) removed', $result));

        } else {
            try {
                $result = $this
                    ->cleanTaskHistory(
                        $taskFilters
                    );
            } catch (\InvalidArgumentException $e) {
                $result = 0;
            }
            $output->writeln(sprintf('Task history : %d filtred item(s) removed', $result));

            try {
                $result = $this
                    ->cleanProcessHistory(
                        $processFilters
                    );
            } catch (\InvalidArgumentException $e) {
                $result = 0;
            }
            $output->writeln(sprintf('Process history : %d filtred item(s) removed', $result));
        }

        if ($input->getOption('remove-orphan-process')) {
            $result = $this
                ->cleanOrphanProcessHistory();
            $output->writeln(sprintf('Process history : %d orphan item(s) removed', $result));
        }
    }

    /**
     * @param array  $input
     * @param string $className
     * @return array
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     */
    protected function extractFilterOptions(array $input, string $className)
    : array {
        $classMetadata = $this
            ->entityManager
            ->getClassMetadata($className);

        $parser = $this;
        array_walk(
            $input,
            function (&$item) use ($parser, $classMetadata) {
                $item = $parser->parseFilter($item, $classMetadata);
            }
        );

        return $input;
    }

    /**
     * @param string        $input
     * @param ClassMetadata $classMetadata
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function parseFilter(string $input, ClassMetadata $classMetadata)
    : array {
        $classProperties = $classMetadata->getFieldNames();
        $pattern = sprintf(
            '/(%s):(%s):(.+)/',
            implode('|', $classProperties),
            implode('|', static::VALID_COMPARISON)
        );
        preg_match($pattern, $input, $parts);

        if (4 !== count($parts)) {
            throw new \InvalidArgumentException(sprintf('Invalid filter %s', $input));
        }

        $filter = array_combine(['filter', 'property', 'comparison', 'value'], $parts);
        $this->fixFilterValue($classMetadata, $filter);

        return $filter;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param array         $filter
     */
    protected function fixFilterValue(ClassMetadata $classMetadata, array &$filter)
    : void {
        if (in_array($filter['comparison'], ['in', 'not in'], true)) {
            $filter['value'] = explode(',', $filter['value']);
        }

        $propertyType = $classMetadata->getTypeOfField($filter['property']);
        if (in_array($propertyType, ['date', 'datetime'], true)) {
            $filter['value'] = new \DateTime($filter['value']);
        }
    }

    /**
     * Remove task history items based en filters
     *
     * @param array[] $filters
     * @return int
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     */
    protected function cleanTaskHistory($filters)
    : int {
        $repository = $this
            ->entityManager
            ->getRepository(TaskHistory::class);

        return $this->cleanHistory($repository, $filters);
    }

    /**
     * Remove task history items based en process and task filters
     *
     * @param array[] $processFilters
     * @param array[] $taskFilters
     * @return int
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     */
    protected function cleanTaskHistoryWithProcessFilters($processFilters, $taskFilters)
    : int {
        if (!$processFilters && !$taskFilters) {
            throw new \InvalidArgumentException('No filter found');
        }

        $queryBuiler = $this
            ->entityManager
            ->getRepository(ProcessHistory::class)
            ->createQueryBuilder('process')
            ->distinct()
            ->leftJoin('process.taskHistories', 'task');

        foreach ($processFilters as $index => $item) {
            $parameterName = sprintf('%s_%d', $item['property'], $index);
            if (in_array($item['comparison'], ['is null', 'is not null', true])) {
                $pattern = 'process.%s %s';
                $queryBuiler
                    ->andWhere(
                        sprintf(
                            $pattern,
                            $item['property'],
                            $item['comparison']
                        )
                    )
                    ->setParameter($parameterName, $item['value']);
            } else {
                $pattern = is_array($item['value']) ? 'process.%s %s (:%s)' : 'process.%s %s :%s';
                /** @noinspection PrintfScanfArgumentsInspection */
                $queryBuiler
                    ->andWhere(
                        sprintf(
                            $pattern,
                            $item['property'],
                            $item['comparison'],
                            $parameterName
                        )
                    )
                    ->setParameter($parameterName, $item['value']);
            }
        }

        foreach ($taskFilters as $index => $item) {
            $parameterName = sprintf('%s_%d', $item['property'], $index);
            if (in_array($item['comparison'], ['is null', 'is not null'], true)) {
                $pattern = 'task.%s %s';
                $queryBuiler
                    ->andWhere(
                        sprintf(
                            $pattern,
                            $item['property'],
                            $item['comparison']
                        )
                    )
                    ->setParameter($parameterName, $item['value']);
            } else {
                $pattern = is_array($item['value']) ? 'task.%s %s (:%s)' : 'task.%s %s :%s';
                $queryBuiler
                    ->andWhere(
                        sprintf(
                            $pattern,
                            $item['property'],
                            $item['comparison'],
                            $parameterName
                        )
                    )
                    ->setParameter($parameterName, $item['value']);
            }
        }

        $iterableResult = $queryBuiler->getQuery()->iterate();

        $i = 0;
        foreach ($iterableResult as $row) {
            $this
                ->entityManager
                ->remove($row[0]);
            if (($i % 100) === 0) {
                $this
                    ->entityManager
                    ->flush();
                $this
                    ->entityManager
                    ->clear();
            }
            ++$i;
        }
        $this
            ->entityManager
            ->flush();
        $this
            ->entityManager
            ->clear();

        return $i;
    }

    /**
     * Remove process history items orphan and those based en filters
     *
     * @param array[] $filters
     * @return int
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     */
    protected function cleanProcessHistory($filters)
    : int {
        $repository = $this
            ->entityManager
            ->getRepository(ProcessHistory::class);

        return $this->cleanHistory($repository, $filters);
    }

    /**
     * Remove process history items without task history
     *
     * @return int
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     */
    protected function cleanOrphanProcessHistory()
    : int
    {
        $taskSubQueryBuilder = $this
            ->entityManager
            ->getRepository(TaskHistory::class)
            ->createQueryBuilder('task')
            ->select('IDENTITY(task.processHistory)');

        $processQueryBuilder = $this
            ->entityManager
            ->getRepository(ProcessHistory::class)
            ->createQueryBuilder('process');
        $processQueryBuilder
            ->delete()
            ->where($processQueryBuilder->expr()->notIn('process', $taskSubQueryBuilder->getDQL()));;

        return $processQueryBuilder->getQuery()->execute();

    }

    /**
     * Remove history items based en filters
     *
     * @param EntityRepository $repository
     * @param array[]          $filters
     * @return int
     * @throws \InvalidArgumentException
     */
    protected function cleanHistory($repository, $filters)
    : int {
        if (!$filters) {
            throw new \InvalidArgumentException('No filter found');
        }

        $queryBuiler = $repository
            ->createQueryBuilder('object')
            ->delete();

        foreach ($filters as $index => $item) {
            $parameterName = sprintf('%s_%d', $item['property'], $index);
            $pattern = is_array($item['value']) ? 'object.%s %s (:%s)' : 'object.%s %s :%s';
            $queryBuiler
                ->andWhere(
                    sprintf(
                        $pattern,
                        $item['property'],
                        $item['comparison'],
                        $parameterName
                    )
                )
                ->setParameter($parameterName, $item['value']);
        }

        return $queryBuiler->getQuery()->execute();
    }
}
