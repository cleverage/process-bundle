StatCounterTask
===============

Counts the number of times the task is executed and logs the total (`info` level) when the process ends.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Reporting\StatCounterTask`

Accepted inputs
---------------

Input is ignored.

Possible outputs
----------------

`null`: the task does not set any output. It is meant to be used as the last task of a branch.

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
