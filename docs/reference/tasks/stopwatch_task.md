StopwatchTask
=============

Logs (at `info` level, on the `cleverage_process_task` channel) every event of the `__root__` section of the Symfony
[Stopwatch component](https://symfony.com/doc/current/components/stopwatch.html). Useful to profile a process.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Debug\StopwatchTask`

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`null`: no output is set

Options
-------

This task has no option.

Examples
--------

* Log stopwatch events

```yaml
# Task configuration level
stopwatch:
  service: '@CleverAge\ProcessBundle\Task\Debug\StopwatchTask'
```
