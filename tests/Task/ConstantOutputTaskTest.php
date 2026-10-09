<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Task;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\ConstantOutputTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(ConstantOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class ConstantOutputTaskTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function outputProvider(): iterable
    {
        yield 'array' => [['id' => 123, 'firstname' => 'Test1']];
        yield 'string' => ['foo'];
        yield 'int' => [42];
        yield 'bool' => [false];
        yield 'null' => [null];
    }

    #[DataProvider('outputProvider')]
    public function testOutputIsTheConfiguredValue(mixed $output): void
    {
        $task = new ConstantOutputTask();
        $state = $this->createState(['output' => $output], 'ignored input');
        $task->initialize($state);

        $task->execute($state);

        self::assertSame($output, $state->getOutput());
        self::assertFalse($state->isSkipped());
    }

    public function testInputIsIgnored(): void
    {
        $task = new ConstantOutputTask();
        $state = $this->createState(['output' => 'constant'], 'first');
        $task->initialize($state);

        $task->execute($state);
        self::assertSame('constant', $state->getOutput());

        $state->setInput(['second']);
        $task->execute($state);
        self::assertSame('constant', $state->getOutput());
    }

    public function testOutputIsResolvedFromContext(): void
    {
        $task = new ConstantOutputTask();
        $state = $this->createState(['output' => '{{ value }}'], null, ['value' => 'from context']);
        $task->initialize($state);

        $task->execute($state);

        self::assertSame('from context', $state->getOutput());
    }

    public function testMissingOutputOptionFailsAtInitialization(): void
    {
        $task = new ConstantOutputTask();
        $state = $this->createState([]);

        $this->expectException(MissingOptionsException::class);
        $task->initialize($state);
    }

    private function createState(array $options, mixed $input = null, array $context = []): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext($context);
        $state->setTaskConfiguration(new TaskConfiguration('constant', ConstantOutputTask::class, $options));
        $state->setInput($input);
        $state->reset(false);

        return $state;
    }
}
