StopTask
========

Immediately stops the process and makes it fail. Unlike [DieTask](die_task.md), the stop is handled by the process
manager: no following task is executed (including remaining iterations), and the process fails as with the `stop` error
strategy. The process manager throws a `ProcessFailedException` (whose previous exception is a
`ProcessStoppedException`), the `cleverage_process.fail` event is dispatched, the tasks are not finalized,
`cleverage:process:execute` exits with a non-zero code and the task running a sub-process
([ProcessExecutorTask](process_executor_task.md)) fails, according to its `error_strategy`. Useful to halt execution in
an error branch.

To end a flow without failure, filter the items instead (e.g. [FilterTask](filter_task.md),
[SkipEmptyTask](skip_empty_task.md)).

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\StopTask`
* **Code**: `stop` (`task: stop`)

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

None, the process fails

Options
-------

This task has no option.

Examples
--------

* Stop the process on the first validation error

```yaml
# Task configuration level
validate:
  task: validator
  error_strategy: skip
  error_outputs: [abort]
abort:
  task: stop
```
