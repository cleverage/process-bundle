Testing
=======

Processes are made of small pieces (transformers, tasks, sub-processes), which makes them easy to test at each level.
The examples below use [PHPUnit](https://phpunit.de) and Symfony's
[testing tools](https://symfony.com/doc/current/testing.html).

## Unit testing transformers

A transformer is a plain PHP object: instantiate it and call `transform()` with an input and options. If it implements
`ConfigurableTransformerInterface`, resolve the options with its `configureOptions()` method first, to test defaults
and validation the same way the bundle does.

```php
namespace App\Tests\Transformer;

use App\Transformer\VatTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[CoversClass(VatTransformer::class)]
class VatTransformerTest extends TestCase
{
    public function testTransformWithDefaultRate(): void
    {
        $transformer = new VatTransformer();

        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);
        $options = $resolver->resolve([]);

        self::assertSame(12.0, $transformer->transform(10, $options));
    }
}
```

Passing the options array directly to `transform()` also works, but skips the defaults and validation: most of the
bundle's own tests in `tests/Transformer/` do so, and test `configureOptions()` separately.

## Unit testing tasks

A task only interacts with the process through its `ProcessState`. To test it in isolation, build a state with a
minimal task and process configuration:

```php
namespace App\Tests\Task;

use App\Task\PrefixTask;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrefixTask::class)]
class PrefixTaskTest extends TestCase
{
    public function testExecute(): void
    {
        $state = $this->createState(['prefix' => 'SKU-']);
        $state->setInput('123');

        $task = new PrefixTask();
        $task->initialize($state); // Resolves (and validates) options
        $task->execute($state);

        self::assertSame('SKU-123', $state->getOutput());
        self::assertFalse($state->isSkipped());
    }

    private function createState(array $options, array $context = []): ProcessState
    {
        $taskConfiguration = new TaskConfiguration('prefix', '@'.PrefixTask::class, $options);
        $processConfiguration = new ProcessConfiguration('test', ['prefix' => $taskConfiguration]);

        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration, $context));
        $state->setTaskConfiguration($taskConfiguration);
        $state->setContext($context);
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->reset(false); // Initializes the output, skipped and error flags, as the process manager does

        return $state;
    }
}
```

For iterable tasks, call `execute()` then `next()` in a loop; for blocking tasks, call `execute()` for each input then
`proceed()`; for finalizable tasks, do not forget to call `finalize()`.

## Functional testing of processes

The best way to test a whole workflow is to execute the process, like the console command does, with the process
manager. Its service id is `cleverage_process.manager.process` (class `CleverAge\ProcessBundle\Manager\ProcessManager`);
it is a private service, available through the test container of a `KernelTestCase`:

```php
public function execute(string $processCode, mixed $input = null, array $context = []): mixed
```

`$input` is given to the process `entry_point`, `$context` is the same as the `--context` option of the command, and
the returned value is the last output of the process `end_point` (`null` if there is none). If the process fails, the
exception is rethrown; but a task error handled by the `stop` error strategy is thrown as a new
`Symfony\Component\ErrorHandler\Error\FatalError` (an `\Error`), which only keeps the original message (the original
exception is not attached as `previous`). Test it with `expectException(FatalError::class)` and
`expectExceptionMessageMatches()` rather than with the original exception class.

```yaml
# config/packages/test/clever_age_process.yaml
clever_age_process:
    configurations:
        test.app.vat:
            entry_point: transform
            end_point: transform
            tasks:
                transform:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            app_vat:
                                rate: '{{ rate }}'
```

```php
namespace App\Tests\Process;

use CleverAge\ProcessBundle\Manager\ProcessManager;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversNothing]
class VatProcessTest extends KernelTestCase
{
    public function testProcess(): void
    {
        self::bootKernel();
        /** @var ProcessManager $processManager */
        $processManager = self::getContainer()->get('cleverage_process.manager.process');

        $result = $processManager->execute('test.app.vat', 100, ['rate' => 0.055]);

        self::assertSame(105.5, $result);
    }
}
```

A few tips:
- Put test-only processes (prefixed, e.g. `test.`) in `config/packages/test/` so they are only loaded in the `test`
  environment, and use `entry_point`/`end_point` to feed and check the data.
- Use [contextual values](01-quick_start.md#contextual-values) for file paths, so each test can use its own fixture
  files and temporary output folder.
- Iterable processes only return the last output of the end point: to check every item, end the process with an
  aggregator (e.g. [AggregateIterableTask](reference/tasks/aggregate_iterable_task.md)) as `end_point`, or write the
  result to a file and check its content.
- To test a sub-process on its own, execute it directly with the process manager: it is a regular process.
- You can also test the console command with Symfony's `CommandTester` on `cleverage:process:execute`, e.g. to check
  the `--input` and `--context` parsing.
