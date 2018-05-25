<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Command;

use CleverAge\ProcessBundle\Entity\ProcessHistory;
use CleverAge\ProcessBundle\Entity\TaskHistory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
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
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class CleanLogCommand extends Command
{
    /** @var array */
    protected const VALID_COMPARISON = [
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        'in',
        'not in',
        'like',
        'is null',
        'is not null',
    ];

    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
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
     *
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
            throw new \InvalidArgumentException("Failed to parse process filters: {$e->getMessage()}");
        }
        try {
            $taskFilters = $this->extractFilterOptions(
                $input->getOption('task-filters'),
                TaskHistory::class
            );
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException("Failed to parse task filters: {$e->getMessage()}");
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
     *
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function extractFilterOptions(array $input, string $className): array
    {
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
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function parseFilter(string $input, ClassMetadata $classMetadata): array
    {
        $classProperties = $classMetadata->getFieldNames();
        $pattern = sprintf(
            '/(%s):(%s):(.+)/',
            implode('|', $classProperties),
            implode('|', static::VALID_COMPARISON)
        );
        preg_match($pattern, $input, $parts);

        if (4 !== \count($parts)) {
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
    protected function fixFilterValue(ClassMetadata $classMetadata, array &$filter): void
    {
        if (\in_array($filter['comparison'], ['in', 'not in'], true)) {
            $filter['value'] = explode(',', $filter['value']);
        }

        $propertyType = $classMetadata->getTypeOfField($filter['property']);
        if (\in_array($propertyType, ['date', 'datetime'], true)) {
            $filter['value'] = new \DateTime($filter['value']);
        }
    }

    /**
     * Remove task history items based en filters
     *
     * @param array[] $filters
     *
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    protected function cleanTaskHistory($filters): int
    {
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
     *
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    protected function cleanTaskHistoryWithProcessFilters($processFilters, $taskFilters): int
    {
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
            if (\in_array($item['comparison'], ['is null', 'is not null'], true)) {
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
                $pattern = \is_array($item['value']) ? 'process.%s %s (:%s)' : 'process.%s %s :%s';
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
            if (\in_array($item['comparison'], ['is null', 'is not null'], true)) {
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
                $pattern = \is_array($item['value']) ? 'task.%s %s (:%s)' : 'task.%s %s :%s';
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
            if (0 === ($i % 100)) {
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
     *
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    protected function cleanProcessHistory($filters): int
    {
        $repository = $this
            ->entityManager
            ->getRepository(ProcessHistory::class);

        return $this->cleanHistory($repository, $filters);
    }

    /**
     * Remove process history items without task history
     *
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    protected function cleanOrphanProcessHistory(): int
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
            ->where($processQueryBuilder->expr()->notIn('process', $taskSubQueryBuilder->getDQL()));

        return $processQueryBuilder->getQuery()->execute();
    }

    /**
     * Remove history items based en filters
     *
     * @param EntityRepository $repository
     * @param array[]          $filters
     *
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    protected function cleanHistory($repository, $filters): int
    {
        if (!$filters) {
            throw new \InvalidArgumentException('No filter found');
        }

        $queryBuiler = $repository
            ->createQueryBuilder('object')
            ->delete();

        foreach ($filters as $index => $item) {
            $parameterName = sprintf('%s_%d', $item['property'], $index);
            $pattern = \is_array($item['value']) ? 'object.%s %s (:%s)' : 'object.%s %s :%s';
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
