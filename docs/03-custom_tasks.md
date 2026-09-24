Custom tasks
============

Once you've worked with provided tasks to build simple processes, you may face cases where you want to build a more
complex workflow. Common tasks would not be powerful enough (or would imply much unoptimized setup), so you'll have to
create your own.

## Service declaration

As stated before, tasks are simple services implementing `CleverAge\ProcessBundle\Model\TaskInterface`, so you can
design them like any other service, with only small specificities:
- the service must be **public**: the process manager fetches it from the container using the `service` reference of
  the task configuration (with or without the leading `@`)
- the service should **not be shared** ([`shared: false`](https://symfony.com/doc/current/service_container/shared.html))

Most of the time you don't want a task declared in a process to share some data with another declaration using the
same service, or even with another instance of your process. Since a task is a service, by default, Symfony will only
create one instance (keeping values in every attribute of your class). With `shared: false`, a new instance is created
each time a task configuration is initialized.

```yaml
# config/services.yaml
services:
    App\Task\:
        resource: '../src/Task/*'
        autowire: true
        autoconfigure: true
        public: true
        shared: false
        tags:
            - { name: monolog.logger, channel: cleverage_process_task }
```

If your `services.yaml` also has the default `App\:` resource (which includes `src/Task/`), declare `App\Task\:` after
it: the last definition of a service wins.

## Using the state object

With the `TaskInterface::execute` method comes a small container object: the state
(`CleverAge\ProcessBundle\Model\ProcessState`).

It's the only way to interact with the rest of the process. Each time the task needs to process data, the `execute`
method is called and the `$state` contains a new input (`ProcessState::getInput`). Once the task is done, it
may pass a new output to the next tasks (`ProcessState::setOutput`).

```php
namespace App\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

class UppercaseNameTask implements TaskInterface
{
    public function execute(ProcessState $state): void
    {
        $item = $state->getInput();
        $item['name'] = mb_strtoupper($item['name']);

        $state->setOutput($item);
    }
}
```

The state also gives access to the context of the execution:
* `ProcessState::getContext()`: the contextual values given to the process (see
  [contextual values](01-quick_start.md#contextual-values))
* `ProcessState::getContextualizedOptions()` / `ProcessState::getContextualizedOption($code, $default)`: the raw task
  options, with context placeholders replaced
* `ProcessState::getProcessConfiguration()` / `ProcessState::getTaskConfiguration()`: the current process and task
  configurations
* `ProcessState::getProcessHistory()`: the current execution (process code, start date, state, duration...)
* `ProcessState::getPreviousState()`: the state of the task that produced the current input

Sometimes, when you execute a task, you need to change how the process continues. Here are the main methods (see
[error management](04-advanced_workflow.md#errors-and-skips) for more details):
* `ProcessState::setSkipped(true)`: the current output won't be passed to the next tasks (the process continues with
  the next input)
* `ProcessState::stop(?\Throwable $e = null)`: with an exception, the process fails; without exception, only the current
  flow stops (other root tasks, blocking tasks and finalization still run, see
  [errors and skips](04-advanced_workflow.md#errors-and-skips))
* `ProcessState::setException(\Throwable $e)`: flag the current execution as failed without throwing, the task
  `error_strategy` is then applied (throwing an exception from `execute` has the same effect)
* `ProcessState::setErrorOutput($value)`: send a value to the error branch of your workflow (the tasks listed in
  `error_outputs`)
* `ProcessState::addErrorContextValue($key, $value)` / `removeErrorContext($key)`: add information to the log record
  written when an error occurs

## Options

To reuse tasks more easily, the best way is to use options. A basic option management implementation is already
available in `CleverAge\ProcessBundle\Model\AbstractConfigurableTask`.

Based on [Symfony's OptionsResolver Component](https://symfony.com/doc/current/components/options_resolver.html) this
abstract class allows you to implement its `configureOptions` method to add your requirements, default values and
normalizers. Options are resolved (once) during the task initialization, and can be read with `getOptions($state)` or
`getOption($state, $code)`. If the resolution fails during initialization, the error is logged and the options are
resolved again (failing the process) when the task is first executed.

```php
namespace App\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrefixTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        $state->setOutput($this->getOption($state, 'prefix').$state->getInput());
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('prefix');
        $resolver->setAllowedTypes('prefix', 'string');
    }
}
```

```yaml
prefix:
    service: '@App\Task\PrefixTask'
    options:
        prefix: 'SKU-'
```

It's a very important step to allow manipulating your custom task. Even when you may have only one instance, and one
purpose, you'll find that having some options will help you debug a situation, or evolve your process.

Note that `AbstractConfigurableTask` implements `Symfony\Contracts\Service\ResetInterface`: resolved options are
cleared when the service is reset. If you override `initialize`, remember to call `parent::initialize($state)`.

## Iterable and Blocking tasks implementations

Defining your tasks as Iterable or Blocking is as simple as implementing one of the corresponding interfaces (see
[task types](02-task_types.md) for the complete lifecycle):
* `CleverAge\ProcessBundle\Model\IterableTaskInterface`: the `next` method should behave almost the same as PHP's
native [next](https://www.php.net/manual/en/function.next.php) function for arrays, except it only returns a boolean:
`true` if there is another item to output (the task is then executed again), `false` when the iteration is over
* `CleverAge\ProcessBundle\Model\BlockingTaskInterface`: every `execute` method call should only accumulate data from
the input, and once every previous task is _resolved_ (all their executions and iterations are over), the `proceed`
method should provide an output

```php
namespace App\Task;

use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

class SumTask implements BlockingTaskInterface
{
    protected int|float $sum = 0;

    public function execute(ProcessState $state): void
    {
        $this->sum += $state->getInput();
    }

    public function proceed(ProcessState $state): void
    {
        $state->setOutput($this->sum);
    }
}
```

It's up to you to know when you should be using one of those, but basically:
* When you loop over a collection of independent elements, you should use an Iterable task. It may help you reduce the
memory footprint.
* When you need to collect, upload, ... data as a whole, then you might need a Blocking task. Be sure to read the
[notice about blocking tasks](02-task_types.md#blocking-tasks) about memory usage.

Tasks should not be both Iterable and Blocking. If you need to buffer data and output it by chunks, look at
`CleverAge\ProcessBundle\Model\FlushableTaskInterface` (see [flushable tasks](02-task_types.md#flushable-tasks)).

## Transformers

Transformers are another kind of service. They implement `CleverAge\ProcessBundle\Transformer\TransformerInterface`
(`transform(mixed $value, array $options = []): mixed` and `getCode(): string`) or
`CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface` (which adds
`configureOptions(OptionsResolver $resolver): void`) and are declared with a `cleverage.transformer` tag.

They're meant to be lightweight, composable, and stateless pieces of your process. Feel free to implement custom ones
as soon as provided ones don't fit your goal.

```php
namespace App\Transformer;

use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VatTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, array $options = []): mixed
    {
        return round($value * (1 + $options['rate']), 2);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('rate', 0.2);
        $resolver->setAllowedTypes('rate', ['float', 'int']);
    }

    public function getCode(): string
    {
        return 'app_vat';
    }
}
```

```yaml
# config/services.yaml
services:
    App\Transformer\:
        resource: '../src/Transformer/*'
        autowire: true
        tags:
            - { name: cleverage.transformer }
```

Once properly set up, they are registered in the `CleverAge\ProcessBundle\Registry\TransformerRegistry` (used by the
`TransformerTask` and every transformer using a sub-list of transformers), using the code from
`TransformerInterface::getCode`. Two transformers cannot share the same code. When creating a new transformer for your
project you should use an internal prefix in your codes to avoid conflicts with potential standard additions.

```yaml
transform:
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    options:
        transformers:
            app_vat:
                rate: 0.055
```

## Logging

The bundle declares three [Monolog channels](https://symfony.com/doc/current/logging/channels_handlers.html):

| Channel | Used by |
| ------- | ------- |
| `cleverage_process` | the process manager: process start/end, tasks processing (debug level), unreachable tasks warnings, critical failures |
| `cleverage_process_task` | the process manager for task errors (with the `log_level` of the task configuration), and every task of the bundle |
| `cleverage_process_transformer` | every transformer of the bundle |

Each channel has a Monolog processor that adds the following values in the `extra` of every record: `process_id`,
`process_code` and `process_context`, plus `task_code`, `task_service` (and the current `error` output or `exception`
if any) for the task and transformer channels.

To benefit from those in your own tasks, inject a `Psr\Log\LoggerInterface` and bind it to the task channel with the
`monolog.logger` tag:

```yaml
services:
    App\Task\:
        resource: '../src/Task/*'
        autowire: true
        public: true
        shared: false
        tags:
            - { name: monolog.logger, channel: cleverage_process_task }
```

```php
namespace App\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Psr\Log\LoggerInterface;

class MyTask implements TaskInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }

    public function execute(ProcessState $state): void
    {
        $this->logger->info('Processing item', ['input' => $state->getInput()]);
        // ...
    }
}
```

Use `cleverage_process_transformer` for your transformers. See the [common setup cookbook](cookbooks/01-common_setup.md#logging)
for a handler configuration example.
