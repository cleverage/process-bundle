Task types
==========

## Task definition

Tasks are Symfony services that implement `CleverAge\ProcessBundle\Model\TaskInterface`, which only defines one
method: `execute(ProcessState $state): void`.

Most of them take an input to produce an output, but others might download a file, write a CSV, load database data...
Step by step, tasks are chained together according to the process workflow definition.

A task can be executed one or multiple times during a process: once for each input it receives from its parents.

On top of `TaskInterface`, a task can implement several optional interfaces (all in the `CleverAge\ProcessBundle\Model`
namespace) that change how the process manager drives it. They are described below.

A task is *resolved* when all its executions are over, as well as those of all its ancestors (see
[task resolution](04-advanced_workflow.md#task-resolution--blocking)).

| Interface | Method | Called |
| --------- | ------ | ------ |
| `TaskInterface` | `execute(ProcessState $state): void` | For each input |
| `IterableTaskInterface` | `next(ProcessState $state): bool` | After each execution, the task is executed again while it returns `true` |
| `BlockingTaskInterface` | `proceed(ProcessState $state): void` | Once, when all parent tasks are resolved, only if the task received at least one input |
| `FlushableTaskInterface` | `flush(ProcessState $state): void` | When an ancestor (or the task itself) is resolved or finishes iterating: possibly several times |
| `InitializableTaskInterface` | `initialize(ProcessState $state): void` | Once, before the process starts |
| `FinalizableTaskInterface` | `finalize(ProcessState $state): void` | Once, at the end of the process |

## Iterable tasks

In most processes, when loading a file or querying a database, you want to manipulate a collection of data. If you're
managing huge amounts of data you might face memory issues.

Iterable tasks are a way to resolve this issue. They implement `CleverAge\ProcessBundle\Model\IterableTaskInterface`.

Each execution produces one output (one item) that is immediately sent to the next tasks (cascading to other tasks).
Then the `next` method is called: while it returns `true`, the task is executed again to produce the next item, until
all data have been processed. This way only one item at a time is in memory.

The main condition to use them is to have no interaction between each chunk of data. If it's not the case, you might
have to look for another way to organize your data, in order to find bigger chunks.

A few examples where iterable tasks are useful:
- You load a collection of database entities: for each of them you want to edit a field and save it back in database.
- In a model with 2 types A and B, where A contains a collection of B: you want to export every B entity that matches a
condition on itself and on its parent. You can iterate on A entities, then if it matches the first condition, iterate
on B entities, check the second condition and finally export them.

Examples of iterable tasks: [CsvReaderTask](reference/tasks/csv_reader_task.md),
[InputIteratorTask](reference/tasks/input_iterator_task.md),
[ConstantIterableOutputTask](reference/tasks/constant_iterable_output_task.md).

## Blocking tasks

Once you produced an iterated flow of data, there can be some point where you need to get the whole result to do a
onetime operation.

Blocking tasks provide a way to block the flow, waiting for all preceding tasks to complete. They implement
`CleverAge\ProcessBundle\Model\BlockingTaskInterface`: `execute` is called for each input but its output is never
passed to the next tasks. Once every parent task is resolved (all their iterations are over), `proceed` is called once
and its output is sent to the next tasks.

`proceed` is only called if the task received at least one input: e.g. a [CsvWriterTask](reference/tasks/csv_writer_task.md)
that receives no line creates no file and outputs nothing, so its next tasks are never executed.

The main category of blocking task is aggregator tasks: they accumulate data until execution. Yet one huge caveat is
they can provoke memory issues (due to their very nature). A strong advice when using them is to control the uphill
amount of data (either with a hard limit or by storing the minimum amount of data). Some examples:
- After loading a collection of entities from database, you can iterate on them to extract and transform some values,
before finally doing a onetime upload of the result as a JSON.
- Once you retrieved some collection of data, you want to check for a global condition such as "there is exactly `XX`
data that fulfill `YY` condition on one field". In this case, instead of storing the full data, you could only store
the field values.

Other blocking tasks might be accumulators: with each input they change some internal data (value, file, ...) without
storing a huge collection. Once there is no input, only the final data is outputted.
The simplest example is a counter, but it can also be a CSV writer: [CsvWriterTask](reference/tasks/csv_writer_task.md)
writes each line on `execute` and outputs the file path on `proceed`.

Examples of blocking tasks: [AggregateIterableTask](reference/tasks/aggregate_iterable_task.md),
[RowAggregatorTask](reference/tasks/row_aggregator_task.md),
[CsvWriterTask](reference/tasks/csv_writer_task.md).

Combining the blocking and iterable behaviors in the same task is not supported.

## Flushable tasks

Flushable tasks sit between normal and blocking tasks: they keep an internal buffer and may output something on some
of their executions (and skip the others), but they need a last chance to output what remains in their buffer once the
flow of data is over.

They implement `CleverAge\ProcessBundle\Model\FlushableTaskInterface`. Each time a task is resolved, and each time an
iterable task finishes its iterations, the process manager browses this task and its following tasks until it reaches a
blocking task, and calls `flush` on every flushable task it finds. The output set during `flush` is passed to the next
tasks as a normal output (call `ProcessState::setSkipped(true)` when there is nothing left to output).

Since every resolved ancestor triggers this walk, `flush` may be called several times on the same task, including when
its buffer is already empty: implementations must be idempotent (output nothing, and skip, when there is nothing new to
flush).

Examples:
- [SimpleBatchTask](reference/tasks/simple_batch_task.md) groups inputs by batches of `batch_count` elements: each
  full batch is outputted during `execute`, and the last incomplete batch during `flush`.
- [CounterTask](reference/tasks/counter_task.md) outputs the count every `flush_every` items, and the current count on
  `flush` (unless it is a multiple of `flush_every`): as `flush` may be called several times, the same count can be
  outputted more than once.

## Initializable tasks

Some tasks may have mandatory initial actions. It may be opening a connection to a remote server, testing if file
permissions are ok, ... But in most of those cases you want to check this setup before actually starting the process.

Initializable tasks, which implement `CleverAge\ProcessBundle\Model\InitializableTaskInterface`, can set up, check
and prepare anything needed for the main execution of the task. All tasks of a process are initialized, in the order
they are configured, before any execution.

This is especially useful (for example) when the process starts with heavy tasks before actually uploading a file, to
detect a problem early. Note however that an exception thrown by `initialize` does not abort the process: it is logged
(`critical` level, on the `cleverage_process_task` channel) and the task state is flagged as stopped, then the process
goes on. The failure only surfaces when the task is first reached: for [configurable tasks](#configurable-tasks-and-options),
the options are resolved again and the process fails at that point (upstream tasks may already have run); for other
tasks, the branch stops after this first execution. If the task is never reached, the process ends normally.

## Configurable tasks and options

Most tasks aim to have a generic behavior. This provides reusability, but each usage needs a slightly different
behavior. Options are a way to configure a task.

Configurable tasks extend `CleverAge\ProcessBundle\Model\AbstractConfigurableTask`. It is an initializable task that
relies on [Symfony's OptionsResolver Component](https://symfony.com/doc/current/components/options_resolver.html):
- you define the options in the abstract `configureOptions(OptionsResolver $resolver)` method
- options are resolved (and validated) during `initialize`, so a misconfigured task is logged as critical before any
  execution; the process itself only fails when the task is first executed (see
  [initializable tasks](#initializable-tasks))
- the options are read from the task `options` configuration, after replacing the `{{ key }}` placeholders with the
  process context values (see [contextual values](01-quick_start.md#contextual-values))
- in `execute`, use `$this->getOptions($state)` or `$this->getOption($state, 'code')` to read the resolved options

See [custom tasks](03-custom_tasks.md#options) for an implementation example.

## Transformers

Transformers are a special subset of this bundle. They're not tasks strictly speaking, but used by them. The main entry
point for Transformers is the [TransformerTask](reference/tasks/transformer_task.md), whose only purpose is to take
some input, pass it through a chain of transformers and transfer the output to the next task.

The idea is to allow a great flexibility (especially using the [MappingTransformer](reference/transformers/mapping_transformer.md)),
without using too much code.

They implement `CleverAge\ProcessBundle\Transformer\TransformerInterface` or
`CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface`. See
[custom tasks](03-custom_tasks.md#transformers) to create your own, and
[generic transformers](reference/03-generic_transformers_definition.md) to build reusable ones from configuration only.

## Finalizable tasks

On the opposite, some tasks may require cleanup work at the very end of the process (e.g. close a file, cleanup a
temporary folder, log some statistics).

Finalizable tasks implement `CleverAge\ProcessBundle\Model\FinalizableTaskInterface`. Their `finalize` method is
called once all tasks are resolved, for every task of the process, in the order they are configured.

Note that finalization only happens when the process reaches its end: if a task stops the process because of an
exception (error strategy `stop`), the process fails immediately and `finalize` is not called. A process stopped
without exception (e.g. by the [StopTask](reference/tasks/stop_task.md)) is still finalized.

Examples of finalizable tasks: [CsvReaderTask](reference/tasks/csv_reader_task.md) and the other CSV tasks (close the
file), [StatCounterTask](reference/tasks/stat_counter_task.md) (logs the number of processed items).
