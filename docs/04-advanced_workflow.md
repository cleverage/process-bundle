Advanced Workflow
=================

## Process execution flow

When a process is executed (with the `cleverage:process:execute` command or with
`CleverAge\ProcessBundle\Manager\ProcessManager::execute($processCode, $input, $context)`), the process manager:

1. dispatches the `cleverage_process.start` event
2. checks the process: circular dependencies are forbidden, and the `entry_point` / `end_point` tasks must be part of
   the *main branch*, the group of connected tasks that is actually executed (see [orphan tasks](#orphan-tasks))
3. **initializes** every task, in the order they are configured: the service is fetched from the container and
   `initialize` is called on [initializable tasks](02-task_types.md#initializable-tasks) (for configurable tasks, this is
   where options are validated). An exception thrown by `initialize` is logged as critical and flags the task as
   stopped, but does not abort the process: it only fails when this task is first executed
4. gives the process input to the `entry_point` task, if one is defined (otherwise the input is ignored and a warning is
   logged)
5. **resolves** the tasks of the main branch (see below)
6. **finalizes** every task, in the order they are configured (see
   [finalizable tasks](02-task_types.md#finalizable-tasks))
7. returns the last output of the `end_point` task (or `null` if there is none) and dispatches the
   `cleverage_process.end` event

If an exception is thrown at any point, the `cleverage_process.fail` event is dispatched and the exception is rethrown.
Note that a task error handled by the `stop` strategy does not surface as the original exception: the process manager
throws a new `Symfony\Component\ErrorHandler\Error\FatalError` (an `\Error`, not an `\Exception`), whose message
contains the process code, the task code and the original message; the original exception is not attached as
`previous` (it is only available in the task error log record).

### Executing a process from PHP

The process manager service id is `cleverage_process.manager.process`. It is private and has no class alias, so it
cannot be autowired by type: inject it explicitly (or declare the alias yourself).

```yaml
# config/services.yaml
services:
    App\Service\ProductImporter:
        arguments:
            $processManager: '@cleverage_process.manager.process'

    # Or, to enable autowiring of the ProcessManager type everywhere:
    CleverAge\ProcessBundle\Manager\ProcessManager: '@cleverage_process.manager.process'
```

```php
$result = $this->processManager->execute('app.import_file', '/tmp/products.csv', ['dry_run' => true]);
```

### Task resolution & blocking

A task is *resolved* when it and all its ancestors are over. Tasks are resolved starting from the end of the
configuration, each task first resolving its parents:
- a **root task** (a task without parent, outside of an error branch) is executed; its output is immediately passed to
  each of its `outputs`, which are executed in turn, and so on (depth-first). An
  [iterable task](02-task_types.md#iterable-tasks) is executed again as long as its `next` method returns `true`,
  each item going through the whole following chain before the next one is produced
- a [blocking task](02-task_types.md#blocking-tasks) receives all the inputs of its parents but does not pass anything
  to its outputs; once all its parents are resolved, `proceed` is called and its output starts a new flow to its
  outputs. If the blocking task never received any input, `proceed` is not called
- when a task is resolved (and when an iterable task finishes its iterations), the task itself and the
  [flushable tasks](02-task_types.md#flushable-tasks) found in its descendants (until a blocking task) are flushed.
  As each resolved ancestor triggers this, a flushable task can be flushed several times: `flush` must be idempotent

The following process reads a CSV file line by line, transforms each line and writes it into another CSV file: the
reader is iterable, the transformer is executed once for each line, and the writer is blocking: it writes each line and
outputs the file path only once, at the end, to the `log` task.

```yaml
clever_age_process:
    configurations:
        app.csv_copy:
            tasks:
                read:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask'
                    options:
                        file_path: '%kernel.project_dir%/var/data/input.csv'
                    outputs: [transform]
                transform:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    id: { code: '[id]' }
                                    name: { code: '[name]' }
                    outputs: [write]
                write:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
                    options:
                        file_path: '%kernel.project_dir%/var/data/output.csv'
                    outputs: [log]
                log:
                    service: '@CleverAge\ProcessBundle\Task\Debug\DebugTask'
```

### Orphan tasks

Only one group of connected tasks, the *main branch*, is executed. It is the group containing the `entry_point` task,
or the `end_point` task if there is no entry point, or else the first configured task. Tasks that are not connected
to it (not referenced in any `outputs` or `error_outputs` of the main branch) are never executed: a warning
`Task '<code>' is unreachable` is logged for each of them. They are still initialized and finalized, like every task of
the process (so a misconfigured orphan task still logs its initialization error). The `cleverage:process:help` command
only displays the main branch.

### Errors and skips

An error happens when a task throws an exception, or flags one with `ProcessState::setException()`. The error is logged
on the `cleverage_process_task` channel with the `log_level` of the task (`critical` by default), then the
`error_strategy` of the task is applied (it defaults to the global `default_error_strategy`, itself `stop` by
default):
- `skip`: the current output is dropped, and the process continues with the next input (e.g. the next line of a CSV
  file)
- `stop`: the whole process stops and fails (a `FatalError` is thrown by the process manager, see
  [process execution flow](#process-execution-flow))

Before applying the strategy, the task input is sent to the tasks listed in `error_outputs` (unless the task already
set a specific value with `ProcessState::setErrorOutput()`). Those tasks form an *error branch*: they are only executed
when an error output is set, and they are never considered as root tasks. A task can also send a value to its error
branch without failing, by calling `setErrorOutput()` (e.g. [FilterTask](reference/tasks/filter_task.md) sends
rejected items to its error outputs). With the `stop` strategy, the error branch is executed before the process
stops.

```yaml
transform:
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    error_strategy: skip          # Continue with the next item on error
    log_level: warning            # Log errors as warning instead of critical
    options:
        transformers:
            mapping:
                mapping:
                    price: { code: '[price]', transformers: { cast: { type: float } } }
    outputs: [write]
    error_outputs: [write_errors] # Receives the input that failed
write_errors:
    service: '@CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamWriterTask'
    options:
        file_path: '%kernel.project_dir%/var/data/errors_{date_time}.json-stream'
```

A task may also control the flow without any error:
- `ProcessState::setSkipped(true)`: the output of this execution is not sent to the next tasks (used by filters, by
  iterable tasks on empty items, by batch tasks while filling a batch...)
- `ProcessState::setStopped(true)` / `ProcessState::stop()`: without exception, only the current flow stops: the stop
  signal goes back up to the root task, which stops iterating. The rest of the resolution still happens: other root
  tasks are executed, blocking tasks that already received inputs are proceeded, flushable tasks are flushed and every
  task is finalized. It is not a failure for the process manager: the `cleverage_process.end` event is dispatched (not
  `cleverage_process.fail`) and the command exits with code 0. The [StopTask](reference/tasks/stop_task.md) also flags
  the process history as failed, but this does not change the event nor the exit code

The legacy `errors` key is a deprecated alias of `error_outputs` (defining both throws an exception).

### Wrapping execution in subprocesses

A process can execute another process with the [ProcessExecutorTask](reference/tasks/process_executor_task.md): for
each input, the sub-process is executed in the same PHP process, with this input given to its `entry_point`, and the
last output of its `end_point` becomes the output of the task (`null` if the sub-process has no `end_point`). Context
values are not inherited: pass them explicitly with the `context` option (placeholders like `{{ key }}` can be used to
forward the parent context).

```yaml
clever_age_process:
    configurations:
        app.import_all:
            tasks:
                list_files:
                    service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
                    options:
                        folder_path: '%kernel.project_dir%/var/import'
                        name_pattern: '*.csv'
                    outputs: [import_file]
                import_file:
                    service: '@CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask'
                    options:
                        process: app.import_file
                        context:
                            dry_run: '{{ dry_run }}' # Always pass -c dry_run:<value>, see below

        app.import_file:
            public: false
            entry_point: read
            tasks:
                read:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask'
                    outputs: [save]
                save:
                    service: '@App\Task\SaveProductTask'
```

If the parent process is executed without a `dry_run` context value, the placeholder is not replaced and the
sub-process receives the literal string `'{{ dry_run }}'`, which is truthy: always provide the forwarded context
values when executing the parent process.

This is a good way to split a big workflow into small, testable processes. Private processes (`public: false`) are
hidden from `cleverage:process:list` (unless `--all` is used) but can still be executed.

## Events

Events are dispatched around process execution (see `CleverAge\ProcessBundle\Event\ProcessEvent`, which gives access
to the process code, input, context and, depending on the event, output or error):

| Event name | Constant | Dispatched |
| ---------- | -------- | ---------- |
| `cleverage_process.start` | `ProcessEvent::EVENT_PROCESS_STARTED` | on process start |
| `cleverage_process.end` | `ProcessEvent::EVENT_PROCESS_ENDED` | on successful process end (with the process output) |
| `cleverage_process.fail` | `ProcessEvent::EVENT_PROCESS_FAILED` | on failed process end (with the associated error) |

Those events are also dispatched for sub-processes executed with the `ProcessExecutorTask`.

Another event is dispatched once by the `cleverage:process:execute` command, before executing any process:
`CleverAge\ProcessBundle\Event\ConsoleProcessEvent`. It is dispatched without a specific name, so listeners must use
the class name as event name. It gives access to the console input/output objects, and to the process input and
context.

```php
namespace App\EventListener;

use CleverAge\ProcessBundle\Event\ProcessEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ProcessEvent::EVENT_PROCESS_FAILED)]
class ProcessFailureListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(ProcessEvent $event): void
    {
        $this->logger->alert("Process {$event->getProcessCode()} failed", [
            'error' => $event->getProcessError()?->getMessage(),
        ]);
    }
}
```

You can also use the [EventDispatcherTask](reference/tasks/event_dispatcher_task.md) to trigger an event in the middle
of a process: it dispatches a `CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent`, giving access to the current
`ProcessState`. Note that in the current implementation the event is dispatched under its class name
(`CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent`): the `event_name` option is required but not passed to the
event dispatcher.

## Parallelization

> **Warning**: in the current version, the `ProcessLauncherTask` and the `CommandRunnerTask` fail when their options
> are resolved, because of known bugs: see the Notes of the [ProcessLauncherTask](reference/tasks/process_launcher_task.md#notes)
> and [CommandRunnerTask](reference/tasks/command_runner_task.md#notes) reference pages.

PHP executes a process in a single thread. To use several CPU cores, the
[ProcessLauncherTask](reference/tasks/process_launcher_task.md) launches a process in a separate system process
(`bin/console cleverage:process:execute --input-from-stdin ...`, in the same environment) for each input it receives:
- at most `max_processes` sub-processes run at the same time; the task waits for a free slot before launching a new one
- the input is cast to string and sent to the sub-process through STDIN, `context` values are passed as
  `--context=<key>:<value>` options (values are cast to string, so arrays cannot be forwarded this way)
- with `json_buffering` enabled, the sub-process runs with `--output-format=json-stream --output=<file>`: the last output
  of its `end_point` is written to a JSON stream file whose path is outputted by the task once the sub-process is over
  (use a [JsonStreamReaderTask](reference/tasks/json_stream_reader_task.md) to read it back). The file is only written
  if the sub-process has an `end_point` whose last output is an array; otherwise, as when `json_buffering` is disabled,
  the task outputs nothing
- if a sub-process fails (non-zero exit code), all the running sub-processes are stopped and the task throws an
  exception

```yaml
parallel_import:
    service: '@CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask'
    options:
        process: app.import_file
        max_processes: 4
```

Since sub-processes do not share memory with the parent process, their input must be small and serializable as a
string (an identifier, a file path, a chunk produced by the [CsvSplitterTask](reference/tasks/csv_splitter_task.md)...).

To execute any other system command (not a process), use the
[CommandRunnerTask](reference/tasks/command_runner_task.md): it runs the `commandline` option synchronously with the
Symfony Process component, sends the task input to its STDIN, and outputs what the command wrote on STDOUT.
