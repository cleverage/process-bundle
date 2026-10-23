Upgrade Guide
=============

## v5.6

One deprecation, and some behaviours change: check the points below.

### Recursive process execution

Executing a process while it is already running, by itself or through its sub-processes (`A → B → A` with the
`process_executor` task), is deprecated since 5.6 (`Since cleverage/process-bundle 5.6: Executing the process "A" while
it is already running (A -> B -> A) is deprecated...`) and will throw an exception in 6.0. A process has only one set of
task instances and states: the second execution resets them while the first one is running, which then returns the
output of the deepest execution.

Iterate with an iterable task (e.g. a reader or `constant_iterable_output` followed by `process_executor`) instead of
recursing, or use the `process_launcher` task, which runs the process in a separate PHP process. Executing the same
sub-process several times in a row is not concerned.

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
(`InvalidArgumentException` naming the service, the tasks and the process): all these tasks ran with the same instance,
initialized with the options of each task in turn. Declare your task services with `shared: false`, as the
autoconfiguration of `TaskInterface` does since 5.4:

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
