StopTask
========

Stops the process on purpose: this is not a failure, but a decision of the process, e.g. not going on when the source
file does not exist. Unlike [DieTask](die_task.md), the stop is handled by the process manager:
* the stop signal goes back up to the root task: no following task is executed, including remaining iterations (other
  root tasks are still executed, see [advanced workflow](../../04-advanced_workflow.md));
* every task is finalized (e.g. a writer closes its file);
* the process ends as **stopped**: its history is flagged as stopped (`ProcessHistory::isStopped()`), the
  `Process <process_code> stopped` log is written (at the `success_level`, see
  [process definition](../01-process_definition.md)), and the `cleverage_process.end` event is dispatched with
  `ProcessEvent::isProcessStopped()` returning `true` (the UI shows the execution as *Stopped*);
* `cleverage:process:execute` exits with code 0, and the task running a sub-process
  ([ProcessExecutorTask](process_executor_task.md)) goes on.

To stop a process because of an error, let the error fail the process instead (error strategy `stop`).

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\StopTask`
* **Code**: `stop` (`task: stop`)

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

None, the process is stopped

Options
-------

This task has no option.

Examples
--------

* Do not go on when the source file does not exist (`-c source:"'path/to/file.csv'"`)

```yaml
# Task configuration level
source_path:
  task: constant_output
  options:
    output: '{{ source }}'
  outputs: [source_exists]
source_exists:
  task: transformer
  options:
    transformers:
      callback:
        callback: file_exists
  outputs: [check_source]
check_source:
  task: filter
  options:
    match:
      '': true
  outputs: [read]
  error_outputs: [stop]
stop:
  task: stop
read:
  task: csv_reader
  options:
    file_path: '{{ source }}'
```

* Stop the process on the first invalid item

```yaml
# Task configuration level
validate:
  task: validator
  error_strategy: skip
  error_outputs: [abort]
abort:
  task: stop
```
