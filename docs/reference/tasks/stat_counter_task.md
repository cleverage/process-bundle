StatCounterTask
===============

Counts the number of times the task is executed and logs the total (`info` level) when the process ends. The input is
passed to the output, so the task can be placed anywhere in a branch.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Reporting\StatCounterTask`

Accepted inputs
---------------

`any`: only the number of executions matters.

Possible outputs
----------------

`any`: the input, unchanged.

At finalization, the message `Processed item count: <count>` is logged.

Options
-------

This task has no option.

Examples
--------

* Count the lines written by a process

```yaml
# Task configuration level
count:
  service: '@CleverAge\ProcessBundle\Task\Reporting\StatCounterTask'
```
