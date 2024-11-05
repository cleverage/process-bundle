StopwatchTask
=============

Log all the __root__ events of the Stopwatch component.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Debug\StopwatchTask`

Accepted inputs
---------------

`any`

Example
-------

```yaml
clever_age_process:
  configurations:
    project_prefix.stopwatch_example:
      tasks:
        stopwatch_example:
          service: '@CleverAge\ProcessBundle\Task\Debug\StopwatchTask'
```