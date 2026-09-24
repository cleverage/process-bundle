DieTask
=======

Stops the process brutally by calling PHP `exit`. The whole PHP script ends immediately: no following task, flush,
blocking task or process end handling is executed. Intended for debugging only; use [StopTask](stop_task.md) to stop a
process cleanly.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Debug\DieTask`

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

None, the script is terminated

Options
-------

This task has no option.

Examples
--------

* Terminate right after the first task

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output: { id: 123 }
  outputs: [die]
die:
  service: '@CleverAge\ProcessBundle\Task\Debug\DieTask'
```
