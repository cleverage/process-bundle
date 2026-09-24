StopTask
========

Immediately stops the process and marks its history as failed. Unlike [DieTask](die_task.md), the stop is handled by
the process manager (no following task is executed, including remaining iterations). Useful to halt execution in an
error branch.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\StopTask`

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

* Stop the process on the first validation error

```yaml
# Task configuration level
validate:
  service: '@CleverAge\ProcessBundle\Task\Validation\ValidatorTask'
  error_strategy: skip
  error_outputs: [abort]
abort:
  service: '@CleverAge\ProcessBundle\Task\StopTask'
```
