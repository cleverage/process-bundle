<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Command;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\TaskInterface;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask;
use CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Describe a process configuration
 * This is a POC, waiting to evolve properly.
 */
#[AsCommand(name: 'cleverage:process:help', description: 'Describe a process', )]
class ProcessHelpCommand extends Command
{
    protected const CHAR_DOWN = '│';

    protected const CHAR_MERGE = '┘';

    protected const CHAR_MULTIMERGE = '┴─';

    protected const CHAR_JUMP = '┿─';

    protected const CHAR_HORIZ = '──';

    protected const CHAR_MULTIEXPAND = '┬─';

    protected const CHAR_EXPAND = '┐';

    protected const CHAR_RECEIVE = '├─';

    protected const CHAR_NODE = '■';

    protected const BRANCH_SIZE = 2;

    protected const INDENT_SIZE = 4;

    public function __construct(
        protected ProcessConfigurationRegistry $processConfigRegistry,
        protected ContainerInterface $container
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('process_code', InputArgument::REQUIRED, 'The code of the process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->getFormatter()
            ->setStyle('fire', new OutputFormatterStyle('red'));

        $processCode = $input->getArgument('process_code');
        $process = $this->processConfigRegistry->getProcessConfiguration($processCode);

        $output->writeln('<comment>Process: </comment>');
        $output->writeln(str_repeat(' ', self::INDENT_SIZE).$processCode);
        $output->writeln('');

        if ($process->getDescription()) {
            $output->writeln('<comment>Description:</comment>');
            $output->writeln(str_repeat(' ', self::INDENT_SIZE).$process->getDescription());
            $output->writeln('');
        }

        if ($process->getHelp()) {
            $output->writeln('<comment>Help:</comment>');
            $helpLines = array_filter(explode("\n", $process->getHelp()));
            foreach ($helpLines as $helpLine) {
                $output->writeln(str_repeat(' ', self::INDENT_SIZE).$helpLine);
            }
            $output->writeln('');
        }

        $output->writeln('<comment>Tasks tree:</comment>');

        $branches = [];

        $taskList = $process->getMainTaskGroup();
        $remainingTasks = $taskList;
        $totalBranches = \count($taskList);
        for ($i = 0; $i < $totalBranches; ++$i) {
            // Find the best task to display
            $nextTaskCode = $this->findBestNextTask($branches, $remainingTasks, $process);

            $this->resolveBranchOutput($branches, $nextTaskCode, $process, $output);

            // Remove the task from the remaining list
            $remainingTasks = array_filter($remainingTasks, static fn ($task): bool => $task !== $nextTaskCode);
        }

        $branches = array_filter($branches);
        if (!empty($branches)) {
            $branchStr = '['.implode(', ', $branches).']';
            $output->writeln("<error>All branches are not resolved : {$branchStr}</error>");
        }

        return Command::SUCCESS;
    }

    /**
     * Try to find a best candidate for next display.
     */
    protected function findBestNextTask(
        array $branches,
        array $taskList,
        ProcessConfiguration $process
    ): int|null|string {
        // Get resolvable tasks
        $taskCandidates = [];
        foreach ($taskList as $taskCode) {
            $task = $process->getTaskConfiguration($taskCode);
            if (empty($task->getPreviousTasksConfigurations())) {
                return $taskCode;
            }

            // Check if task has all necessary ancestors in branches
            $hasAllAncestors = array_reduce(
                $task->getPreviousTasksConfigurations(),
                static fn ($result, TaskConfiguration $prevTask): bool => $result && \in_array(
                    $prevTask->getCode(),
                    $branches,
                    true
                ),
                true
            );

            if ($hasAllAncestors) {
                $taskCandidates[] = $taskCode;
            }
        }

        if (empty($taskCandidates)) {
            throw new \UnexpectedValueException('Cannot find a task to output');
        }

        // Try to find the task the most on the right
        $taskWeights = [];
        foreach ($taskCandidates as $taskCandidate) {
            $weight = 0;
            $task = $process->getTaskConfiguration($taskCandidate);
            foreach ($task->getPreviousTasksConfigurations() as $prevTask) {
                $key = array_search($prevTask->getCode(), $branches, true);

                // Should never be non-numeric...
                if (!is_numeric($key)) {
                    throw new \UnexpectedValueException('Invalid key type');
                }
                $weight += $key;
            }

            if (!empty($task->getPreviousTasksConfigurations())) {
                $weight /= \count($task->getPreviousTasksConfigurations());
            }

            $taskWeights[$taskCandidate] = $weight;
        }

        arsort($taskWeights);
        $bestCandidate = key($taskWeights);
        $bestWeight = $taskWeights[$bestCandidate];

        $equalWeights = array_filter($taskWeights, static fn ($item): bool => $item === $bestWeight);

        if (1 === \count($equalWeights)) {
            return $bestCandidate;
        }

        // If a few tasks have the same weight, return the tasks with the lowest number of children
        $childCounts = [];
        foreach ($equalWeights as $taskCode => $weight) {
            $task = $process->getTaskConfiguration($taskCode);
            $childCounts[$taskCode] = $this->getTaskChildrenCount($task);
        }
        asort($childCounts);

        return key($childCounts);
    }

    /**
     * Get the number of children (error or not) of a task.
     */
    protected function getTaskChildrenCount(TaskConfiguration $task): int
    {
        $count = 0;

        foreach ($task->getNextTasksConfigurations() as $nextTasksConfiguration) {
            $count += 1 + $this->getTaskChildrenCount($nextTasksConfiguration);
        }

        foreach ($task->getErrorTasksConfigurations() as $errorTasksConfiguration) {
            $count += 1 + $this->getTaskChildrenCount($errorTasksConfiguration);
        }

        return $count;
    }

    /**
     * Merge needed branches, display a task node, split following needed branches.
     */
    protected function resolveBranchOutput(
        array &$branches,
        string $taskCode,
        ProcessConfiguration $process,
        OutputInterface $output
    ): void {
        $task = $process->getTaskConfiguration($taskCode);
        $branchesToMerge = [];
        $gapBranches = [];
        $origin = null;
        $final = null;

        // Get unique previous branches
        $previousTasks = [];
        foreach ($task->getPreviousTasksConfigurations() as $previousTasksConfiguration) {
            $previousTasks[$previousTasksConfiguration->getCode()] = $previousTasksConfiguration;
        }

        // Check previous branches
        if (empty($previousTasks)) {
            $branches[] = $task->getCode();
        } elseif (1 === \count($previousTasks)) {
            $prevTask = current($previousTasks)
                ->getCode();
            foreach (array_reverse($branches, true) as $i => $branchTask) {
                if ($branchTask === $prevTask) {
                    $branches[$i] = $taskCode;
                    break;
                }
            }
        } else {
            foreach ($previousTasks as $prevTask) {
                $foundBranch = false;
                foreach (array_reverse($branches, true) as $i => $branchTask) {
                    if ($branchTask === $prevTask->getCode()) {
                        $branchesToMerge[] = $i;
                        $foundBranch = true;
                        break;
                    }
                }

                if (!$foundBranch) {
                    $output->writeln(
                        "<error>Could not find previous branch : {$taskCode} depends on {$prevTask->getCode()}</error>"
                    );
                }
            }

            // Don't touch the 1st branch to merge
            sort($branchesToMerge);

            $gapFrom = null;
            foreach ($branchesToMerge as $i) {
                $gapTo = $i;
                if (null !== $gapFrom) {
                    for ($j = $gapFrom + 1; $j < $gapTo; ++$j) {
                        $gapBranches[] = $j;
                    }
                }
                $gapFrom = $i;
            }

            $origin = array_shift($branchesToMerge);
            $final = $gapFrom;
            $branches[$origin] = $taskCode;
        }

        // Merge branches
        if (!empty($branchesToMerge)) {
            $this->writeBranches($output, $branches);

            $this->writeBranches(
                $output,
                $branches,
                '',
                static fn ($taskCode, $i): bool => \in_array($i, $branchesToMerge, true)
                    || \in_array($i, $gapBranches, true)
                    || $i === $origin,
                static function ($taskCode, $i) use ($gapBranches, $origin, $final, $branches): string {
                    if ($i === $origin) {
                        return self::CHAR_RECEIVE;
                    }
                    if (\in_array($i, $gapBranches, true)) {
                        if (null !== $branches[$i]) {
                            return self::CHAR_JUMP;
                        }

                        return self::CHAR_HORIZ;
                    }

                    if ($i === $final) {
                        return self::CHAR_MERGE;
                    }

                    return self::CHAR_MULTIMERGE;
                }
            );

            foreach ($branches as $i => $branchTask) {
                if (\in_array($i, $branchesToMerge, true)) {
                    $branches[$i] = null;
                }
            }
            $this->writeBranches($output, $branches);
        }

        // Cleanup empty trailing branches
        foreach (array_reverse($branches, true) as $i => $branchTask) {
            if (null !== $branchTask) {
                $branches = \array_slice($branches, 0, $i + 1);
                break;
            }
        }

        // Write main line
        $nodeStr = self::CHAR_NODE;
        if ($task->isInErrorBranch()) {
            $nodeStr = "<fire>{$nodeStr}</fire>";
        }

        $this->writeBranches(
            $output,
            $branches,
            $this->getTaskDescription($task),
            static fn ($branchTask, $i): bool => $branchTask === $taskCode,
            $nodeStr
        );

        // Write task help message
        if ($output->isVerbose() && $task->getHelp()) {
            $helpLines = array_filter(explode("\n", $task->getHelp()));
            foreach ($helpLines as $helpLine) {
                $helpMessage = str_repeat(' ', self::INDENT_SIZE)."<info>{$helpLine}</info>";
                $this->writeBranches($output, $branches, $helpMessage);
            }
        }

        // Check next tasks
        $nextTasks = array_unique(
            array_map(
                static fn (TaskConfiguration $task): string => $task->getCode(),
                array_merge($task->getNextTasksConfigurations(), $task->getErrorTasksConfigurations())
            )
        );
        if (\count($nextTasks) > 1) {
            $this->writeBranches($output, $branches);
            array_shift($nextTasks);
            $origin = array_search($taskCode, $branches, true);
            $expandBranches = [];
            foreach ($nextTasks as $nextTask) {
                $index = array_search(null, $branches, true);
                if (false !== $index && $index >= $origin) {
                    /* @var int $index */
                    $branches[$index] = $taskCode;
                    $expandBranches[] = $index;
                } else {
                    $expandBranches[] = \count($branches);
                    $branches[] = $taskCode;
                }
            }
            $gapBranches = [];
            sort($expandBranches);
            $gapFrom = $origin;
            $gapTo = null;

            foreach ($expandBranches as $i) {
                $gapTo = $i;
                for ($j = $gapFrom + 1; $j < $gapTo; ++$j) {
                    $gapBranches[] = $j;
                }
                $gapFrom = $i;
            }
            $final = $gapFrom;

            $this->writeBranches(
                $output,
                $branches,
                '',
                static fn ($branchTask, $i): bool => $i >= $origin && $i <= $final,
                static function ($branchTask, $i) use ($origin, $branches, $gapBranches, $final): string {
                    if ($i === $origin) {
                        return self::CHAR_RECEIVE;
                    }
                    if (\in_array($i, $gapBranches, true)) {
                        if (null !== $branches[$i]) {
                            return self::CHAR_JUMP;
                        }

                        return self::CHAR_HORIZ;
                    }
                    if ($final === $i) {
                        return self::CHAR_EXPAND;
                    }

                    return self::CHAR_MULTIEXPAND;
                }
            );
        }

        if (empty($nextTasks)) {
            foreach ($branches as $i => $branchTask) {
                if ($branchTask === $taskCode) {
                    $branches[$i] = null;
                }
            }
        }

        // Cleanup empty trailing branches
        foreach (array_reverse($branches, true) as $i => $branchTask) {
            if (null !== $branchTask) {
                $branches = \array_slice($branches, 0, $i + 1);
                break;
            }
        }

        $this->writeBranches($output, $branches);
    }

    protected function writeBranches(
        OutputInterface $output,
        array $branches,
        string|iterable $comment = '',
        callable $match = null,
        string|callable $char = null
    ): void {
        $output->write(str_repeat(' ', self::INDENT_SIZE));

        // Merge lines
        foreach ($branches as $i => $branchTask) {
            $str = '';
            if (null !== $match && $match($branchTask, $i)) {
                if (\is_string($char)) {
                    $str = $char;
                } elseif (\is_callable($char)) {
                    $str = $char($branchTask, $i);
                } else {
                    throw new \InvalidArgumentException('Char must be string|callable');
                }
            } elseif (null !== $branchTask) {
                $str = self::CHAR_DOWN;
            }

            // Str_pad does not work with unicode ?
            $noFormatStrLen = mb_strlen(preg_replace('/<[^>]*>/', '', (string) $str));
            for ($j = $noFormatStrLen; $j < self::BRANCH_SIZE; ++$j) {
                $str .= ' ';
            }
            $output->write($str);
        }
        $output->writeln($comment);
    }

    protected function getTaskDescription(TaskConfiguration $task): string
    {
        $description = $task->getCode();
        $interfaces = [];
        $subprocess = [];
        $taskService = $this->getTaskService($task);

        if ($taskService instanceof IterableTaskInterface) {
            $interfaces[] = 'Iterable';
        }

        if ($taskService instanceof BlockingTaskInterface) {
            $interfaces[] = 'Blocking';
        }

        if ($taskService instanceof FlushableTaskInterface) {
            $interfaces[] = 'Flushable';
        }

        if ($taskService instanceof ProcessExecutorTask || $taskService instanceof ProcessLauncherTask) {
            $subprocess[] = $task->getOption('process');
        }

        if (\count($interfaces)) {
            $description .= ' <info>('.implode(', ', $interfaces).')</info>';
        }

        if (\count($subprocess)) {
            $description .= ' <fire>{'.implode(', ', $subprocess).'}</fire>';
        }

        if ($task->getDescription()) {
            $description .= " <comment>{$task->getDescription()}</comment>";
        }

        return $description;
    }

    protected function getTaskService(TaskConfiguration $taskConfiguration): TaskInterface
    {
        // Duplicate code from \CleverAge\ProcessBundle\Manager\ProcessManager::initialize
        // @todo Refactor this using a Registry with this feature:
        // https://symfony.com/doc/current/service_container/service_subscribers_locators.html
        $serviceReference = $taskConfiguration->getServiceReference();
        if (str_starts_with($serviceReference, '@')) {
            $task = $this->container->get(ltrim($serviceReference, '@'));
        } elseif ($this->container->has($serviceReference)) {
            $task = $this->container->get($serviceReference);
        } else {
            throw new \UnexpectedValueException("Unable to resolve service reference for Task '{$taskConfiguration->getCode()}'");
        }
        if (!$task instanceof TaskInterface) {
            throw new \UnexpectedValueException("Service defined in Task '{$taskConfiguration->getCode()}' is not a TaskInterface");
        }

        return $task;
    }
}
