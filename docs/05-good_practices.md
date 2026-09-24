Good practices
==============

## Keep transformers pure

A transformer should behave like a pure function:
- the same input (and options) always gives the same output
- it has no side effect: no database write, no file, no API call, no state kept between two calls

This makes transformers predictable, reusable in any process, and trivial to unit test. Anything with a side effect
(reading, writing, calling a remote service) belongs in a task. If a transformation is expensive and deterministic,
wrap it in the [CachedTransformer](reference/transformers/cached_transformer.md) instead of adding a cache in your own
transformer.

Prefer composing existing transformers (`mapping`, `callback`, `cast`, `implode`, ...) and, when the same chain is used
in several places, declare it once as a [generic transformer](reference/03-generic_transformers_definition.md).

## Declare tasks as non-shared services

Task services must be `public: true` and should be `shared: false`: a task often keeps state in its properties (an
open file, a buffer, a counter...). A shared service would share this state between two tasks using the same service
in a process, or between two executions of a process in the same PHP process (sub-processes, workers, tests). See
[service declaration](03-custom_tasks.md#service-declaration).

## Split big workflows into small processes

A process with dozens of tasks is hard to read, to debug and to test. Split it into small processes that do one thing
(read a file, import one item, export a batch...) and chain them with the
[ProcessExecutorTask](reference/tasks/process_executor_task.md) (see
[subprocesses](04-advanced_workflow.md#wrapping-execution-in-subprocesses)):
- each sub-process can be executed and tested on its own, with an `entry_point` for its input and an `end_point` for
  its output
- sub-processes that should not be launched directly can be marked `public: false`
- the same small process can be reused by several parent processes, or parallelized with the
  [ProcessLauncherTask](reference/tasks/process_launcher_task.md) (currently broken by a known bug, see the
  [Notes](reference/tasks/process_launcher_task.md#notes) of its reference page)

Use `description` and `help` on processes and tasks: they are displayed by `cleverage:process:list` and
`cleverage:process:help`.

## Name processes with a prefix

Process codes are global to the application, and so are transformer codes. Prefix them with your project or domain
(e.g. `app.catalog.import_products`, `app_vat`) to avoid conflicts with processes and transformers provided by
bundles. Organizing configuration files like the codes (e.g. `config/packages/process/app/catalog/import_products.yaml`)
makes them easy to find.

## Handle errors explicitly

- Keep the global `default_error_strategy: stop` (the default), so any unexpected error stops the process instead of
  silently dropping data.
- Set `error_strategy: skip` only on the tasks where an item can fail without compromising the others (e.g. the
  transformation of one line of a file), and tune their `log_level`.
- Plug an error branch (`error_outputs`) on those tasks to keep track of rejected items (write them to a file, log
  them...), rather than losing them.

See [errors and skips](04-advanced_workflow.md#errors-and-skips).

## Mind the memory

- Stream data with [iterable tasks](02-task_types.md#iterable-tasks) (CSV/JSON stream/line readers, Doctrine
  iterators...) instead of loading whole collections: only one item at a time goes through the process.
- Use [blocking tasks](02-task_types.md#blocking-tasks) (aggregators) only when you really need the whole data set,
  and store only what you need in them. Prefer accumulators that write as they receive data (like the
  [CsvWriterTask](reference/tasks/csv_writer_task.md)).
- When you need batches (e.g. for bulk database writes), use the
  [SimpleBatchTask](reference/tasks/simple_batch_task.md) rather than an aggregator.
- Measure: see the [memory usage cookbook](cookbooks/memory_usage_graph.md), and always check memory in the `prod`
  environment (the `dev` environment keeps logs and debug data in memory).

## Do not rely on the execution order of branches

When a task has several `outputs`, each output is fully processed one after the other, in the configured order, but
this is an implementation detail. If a task needs the result of another branch, make the dependency explicit in the
graph (e.g. with a blocking task or an [InputAggregatorTask](reference/tasks/input_aggregator_task.md)) rather than
relying on the order in which branches are executed.

## Use the context for runtime parameters

Values that change between executions (a file name, a date, a limit...) should not be hard-coded in the process
configuration: pass them as [contextual values](01-quick_start.md#contextual-values) (`-c key:value`) and use
`{{ key }}` placeholders in the task options.
