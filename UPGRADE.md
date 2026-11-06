Upgrade Guide
=============

## Latest

### Stop strategy and error branch ending on a stop

A task failing with the `stop` error strategy (the default) makes the process fail, even when its error branch stops
the flow (a `StopTask`, or a task calling `ProcessState::stop()`). Before, the failure was swallowed: the process ended
as succeeded or stopped. To stop the process on purpose on the first error (not a failure), set `error_strategy: skip`
on the failing task, as in the "Stop the process on the first invalid item" example of the
[StopTask](docs/reference/tasks/stop_task.md).

### Counter task

A negative `flush_every` option of the `counter` task works as its absolute value (`-3` as `3`): it is deprecated since
5.10 (`Since cleverage/process-bundle 5.10: A negative "flush_every" option of the counter task (-3) is deprecated...`)
and will be rejected in 6.0: use a positive integer. `flush_every: 0`, which failed on the first execution (`Modulo by
zero`), is now rejected when the options are resolved.

### Dashes in the generic transformers

Since 5.9, the codes of the transformers and of the contextual options of a generic transformer keep their dashes, as
written. Before 5.9, they were replaced by underscores (unless the code also had an underscore), and only their former
name worked: `array-first` was found as the `array_first` transformer, and a `the-prefix` contextual option was given
and used as `the_prefix` / `{{ the_prefix }}`. These former names work again but are deprecated since 5.10 (`Since
cleverage/process-bundle 5.10: The transformer "array-first" of the generic transformer...`, `Using the contextual option
"the-prefix" of the generic transformer...`), and will not work in 6.0: write the code of the transformer as registered
(`array_first`), and the contextual option as defined in `contextual_options` (`the-prefix`, `{{ the-prefix }}`).

## v5.8

### Unknown task references

A task code referenced by the `outputs` or the `error_outputs` of a task, or by the `entry_point` or the `end_point` of
a process, and that is not a task of this process, is deprecated since 5.8 when the container is compiled (`Since
cleverage/process-bundle 5.8: The task "missing" in the "outputs" of the task "read" of the process "app.import" is not
a task of this process...`). Such a process already failed when executed, and is left out of the process lists since
5.6. In 6.0, the container will not compile: fix the reference, or remove the process.

## v5.7

### Dashes in the codes

The codes of the processes, of their tasks and of the generic transformers keep their dashes. Before 5.7, the Symfony
configuration replaced them by underscores (unless the code also had an underscore): `demo.x-y` was registered as
`demo.x_y`, while the references to it kept the dash and failed (`Unknown process demo.x-y`, `No task configuration
with code : next-task`, `No transformer with code : to-upper`). Such codes now work as written, and `cleverage:process:list`
shows them with their dashes.

If you used the former name (with underscores) of such a code, it still works but is deprecated since 5.7
(`Since cleverage/process-bundle 5.7: Using the process "demo.x-y" by the name "demo.x_y"...`), and will not be found in
6.0: use the code as written in the configuration, in the commands (`cleverage:process:execute demo.x-y`), the
`process_executor` tasks, the task references (`outputs`, `error_outputs`, `entry_point`, `end_point`), the transformer
references and the applications storing process codes (e.g. the schedules of the UI bundle).

### Duplicate task references

Listing a task several times in the `outputs` or the `error_outputs` of a task (e.g. `outputs: [next, next]`) is
deprecated since 5.7 (`Since cleverage/process-bundle 5.7: Listing the task "next" several times in the "outputs"...`):
the task is executed once per occurrence, and an exception will be thrown in 6.0. List it once.

## v5.6

Two deprecations, and some behaviours change: check the points below.

### Recursive process execution

Executing a process while it is already running, by itself or through its sub-processes (`A → B → A` with the
`process_executor` task), is deprecated since 5.6 (`Since cleverage/process-bundle 5.6: Executing the process "A" while
it is already running (A -> B -> A) is deprecated...`) and will throw an exception in 6.0. A process has only one set of
task instances and states: the second execution resets them while the first one is running, which then returns the
output of the deepest execution.

Iterate with an iterable task (e.g. a reader or `constant_iterable_output` followed by `process_executor`) instead of
recursing, or use the `process_launcher` task, which runs the process in a separate PHP process. Executing the same
sub-process several times in a row is not concerned.

### Shared task services

A shared service referenced by several tasks of the same process is no longer rejected when the container is compiled
(5.5): it is deprecated since 5.6, and will be rejected in v6.0. The deprecation names the service, the tasks and the
process: declare the service with `shared: false` (see v5.5 below).

### Finalization of the tasks when the process fails

`FinalizableTaskInterface::finalize()` is now also called when the process fails, on every task that has been
initialized (not on a task whose `initialize()` failed, nor on the tasks not reached). A custom finalizable task must not
assume that the process succeeded nor that its data are complete: only release its resources (close files, free
cursors), do not write a footer, move / publish a partial file or run long operations. Check
`$state->getProcessHistory()->isFailed()` in `finalize()` to skip such work when the process has failed.
`StatCounterTask` (`stat_counter`) now logs its count when the process fails too.

## v5.5

No deprecation in this version. Some behaviours change: check the points below.

### Shared task services

A shared service referenced by several tasks of the same process is rejected when the container is compiled
(`InvalidArgumentException` naming the service, the tasks and the process; deprecated instead since 5.6, see above):
all these tasks ran with the same instance, initialized with the options of each task in turn. Declare your task
services with `shared: false`, as the autoconfiguration of `TaskInterface` does since 5.4:

```yaml
# config/services.yaml
services:
    App\Task\MyTask:
        shared: false
```

### `stop` task

A process stopped by the `stop` task (`StopTask`) is no longer flagged as failed: it is stopped on purpose (e.g. the
source file does not exist). It still ends normally (tasks finalized, `cleverage_process.end` event, exit code 0, parent
process going on), but:

| Before                                                   | After                                                     |
|----------------------------------------------------------|-----------------------------------------------------------|
| `ProcessHistory::getState()` returns `failed`            | it returns `stopped` (`ProcessHistory::isStopped()`)      |
| `Process <code> failed` log, at the `failed_level`       | `Process <code> stopped` log, at the `success_level`      |
| nothing tells a stopped process on the end event         | `ProcessEvent::isProcessStopped()` returns `true`         |

To be alerted when a process is stopped, listen to `cleverage_process.end` and check `isProcessStopped()`. To stop a
process because of an error, let the error fail it (error strategy `stop`).

### `cleverage:process:execute`

- The process codes and the output format are checked before any process is executed.
- With `--output-format`, the output is printed to STDOUT whatever the verbosity (it required `-vv`): a script reading
  STDOUT now receives it.
- A non-array output that cannot be stored as JSON stream is reported on the error output.
- `--output-format=dump` without `symfony/var-dumper`, or an unknown format, makes the command fail before any process
  is executed: it was ignored for STDOUT without `-vv`. Install `symfony/var-dumper` or fix the option.

### Tasks and transformers

- `line_reader` / `input_line_reader`: the last line of a file that does not end with a line break is read (it was
  lost).
- `yaml_writer`: a write failure throws a `RuntimeException` (in production, it was ignored), the parent directory is
  created if needed.
- `input_csv_reader`: an input that is not a path (e.g. `null`) throws an `UnexpectedValueException` (instead of a PHP
  warning followed by a `TypeError`).
- `array_element` / `array_last`: a position outside the array (or an empty array) gives `null`, also in debug mode
  (it failed with an "Undefined array key" warning); a negative `index` of `array_element` before the start of the
  array gives `null` instead of the first element.
- `file_splitter`, `csv_splitter` and the `json_buffering` of `process_launcher` create their temporary files readable
  by their owner only (`0600`): a process reading them must run with the same user.

### `ProcessManager` subclasses

- `$taskRegistry` is typed `TaskRegistryInterface` (it was `TaskRegistry`), as the constructor argument.
- The keys of `$processedIterables` and `$processedBlockings` are the `spl_object_id()` of the task configurations (they
  were the task codes): a task of a sub-process may have the same code as a task of its parent.

### Long-running workers

`ProcessConfigurationRegistry` is reset between two messages of a worker (`kernel.reset`): the task instances and their
states (inputs, outputs, aggregated data) of the last execution are released. A task must not rely on data kept from a
previous message.

## v5.4

Unless stated otherwise, the deprecations below trigger a notice (`Since cleverage/process-bundle 5.4: ...`) and will be
removed in 6.0.

### Dependencies

`symfony/process`, `symfony/stopwatch`, `symfony/var-dumper`, `symfony/monolog-bundle` and `ext-intl` are no longer
required, but suggested; `symfony/dotenv`, `symfony/runtime`, `symfony/error-handler` and `symfony/monolog-bridge` are
removed. Require them in your application if you use them:

| Package                  | Needed by                                                |
|--------------------------|----------------------------------------------------------|
| `symfony/process`        | `command_runner` and `process_launcher` tasks            |
| `symfony/stopwatch`      | `stopwatch` task                                         |
| `symfony/var-dumper`     | `debug` task, `dump` transformer, `--output=-` (dump)    |
| `symfony/monolog-bundle` | `cleverage_process*` log channels and processors         |
| `ext-intl`               | `slugify` transformer                                    |

Without them, the container still compiles: the related tasks and transformer fail with an explicit message when used,
nothing is dumped, and the default logger is used. The doctrine, soap and ui process bundles must be `>=3.3`.

### Task reference: `service` → `task`

Referencing a task by its service is deprecated, use its code (given on the reference page of each task):

```yaml
tasks:
    my_task:
        # Before (deprecated)
        service: '@CleverAge\ProcessBundle\Task\TransformerTask'
        # After
        task: transformer
```

Give a code to your own tasks with the `#[AsTask(code: 'my_task')]` attribute (autoconfiguration) or the `code`
attribute of the `cleverage.task` tag. Unknown or duplicate codes fail the container compilation.

### Task configuration

- `errors` is deprecated, use `error_outputs` (and `TaskConfiguration::getErrorOutputs()` instead of `getErrors()`).
  The unused `TaskConfiguration::$logErrors` property is deprecated.
- An `error_strategy` other than `skip` or `stop` is deprecated (it made the process fail at runtime), it will be
  rejected in 6.0.

### Transformer codes

Registering a transformer without code is deprecated: declare its code with the `#[AsTransformer(code: 'my_code')]`
attribute or the `code` attribute of the `cleverage.transformer` tag, so that it is only instantiated when used.

```yaml
# config/services.yaml
App\Transformer\MyTransformer:
    tags: [{ name: cleverage.transformer, code: my_code }]
```

`TransformerRegistry::addTransformer()` is deprecated. `TransformerInterface::getCode()` is deprecated (no runtime
notice) and will be removed from the interface in 6.0: keep implementing it until then, returning the code of the tag.

### Services

- The tasks and transformers of the bundle, and the tasks of your application made public by the `TaskInterface`
  autoconfiguration, will be private: fetching them from the service container (`$container->get()`) is deprecated,
  use dependency injection.
- A task not referenced by the process configurations known at compile time is fetched from the service container,
  with a deprecation: it will not be found in 6.0.
- The `cleverage_process.event_listener.data_queue` service and `DataQueueEventListener` are deprecated (unbounded
  memory usage): write your own listener of the event dispatched by the `event_dispatcher` task.
- `RegistryCompilerPass` and `CheckSerializerCompilerPass` are deprecated (no longer used).

### Final classes

The classes not meant to be extended are marked `@final` and will be `final` in 6.0 (engine, registries, models,
configurations, events, concrete tasks and transformers); extending them triggers a deprecation with the Symfony
`DebugClassLoader`. Type your services on the new interfaces, autowired by the bundle:

| Class                           | Interface                                |
|---------------------------------|------------------------------------------|
| `ProcessManager`                | `ProcessManagerInterface`                |
| `ProcessConfigurationRegistry`  | `ProcessConfigurationRegistryInterface`  |
| `TransformerRegistry`           | `TransformerRegistryInterface`           |
| `TaskRegistry`                  | `TaskRegistryInterface`                  |

The infrastructure classes (extension, configuration, commands, loggers, Monolog processors...) are `@internal`.

### Constructor arguments

Only relevant if you instantiate or decorate these services yourself:

| Class                                       | Before (deprecated)               | After                                             |
|---------------------------------------------|-----------------------------------|---------------------------------------------------|
| `ProcessManager`                            | no `TaskRegistry` (last argument) | `TaskRegistry`                                    |
| `ProcessHelpCommand`                        | service container                 | `TaskRegistry`                                    |
| `CommandRunnerTask`                         | `KernelInterface`                 | `%kernel.project_dir%`                            |
| `ProcessLauncherTask`, `SubprocessInstance` | `KernelInterface`                 | console path and `%kernel.environment%`           |

The `$kernel` parameter of these three constructors is renamed: `$projectDir` (`CommandRunnerTask`) and `$consolePath`
(`ProcessLauncherTask`, `SubprocessInstance`). Passing the kernel is deprecated, but passing the argument by its name
breaks: a service definition (`arguments: { $kernel: '@kernel' }`) or a call (`new CommandRunnerTask(kernel: $kernel)`)
using the old name must use the new one.

### Exceptions

The bundle throws its own exceptions implementing `ProcessExceptionInterface` (`InvalidArgumentException`,
`LogicException`, `RuntimeException`, `UnexpectedValueException`), extending the SPL exceptions thrown before: existing
`catch` blocks keep working, and `ProcessExceptionInterface` now catches all the errors of the bundle.

## v5.2

### Mapping transformer: literal keys

Setting a target property that is not a simple name (e.g. `a.b`) as a literal key of an array destination is
deprecated, it will throw an `UnexpectedValueException` in 6.0. Use the index notation:

```yaml
mapping:
    # Before (deprecated): sets the "a.b" key
    a.b: { code: '[source]' }
    # After
    '[a.b]': { code: '[source]' }  # literal key
    '[a][b]': { code: '[source]' } # nested arrays
```

## v5.1

### Event dispatcher task

With the `event_name` option set, the event is dispatched under this name. Listening to `EventDispatcherTaskEvent`
for such a task is deprecated, listen to the `event_name` instead.

### Task initialization failure

When the initialization of a task fails, the process goes on with a deprecation. In 6.0, it will fail before executing
any task.

### Process launcher task

The `process_options` option of the `process_launcher` task is deprecated (any non-empty value already throws an
exception): remove it from your configurations, it will be removed in 6.0.

## v2.0.4

### Callback transformer

The `additional_parameters` option is deprecated, use `right_parameters`.
