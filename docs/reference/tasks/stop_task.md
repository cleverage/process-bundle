StopTask
========

Immediately stop the process and mark it as failed. Useful to halt execution in an error branch.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\StopTask`

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

No output is set. The process is stopped and marked as failed.

Example
-------

```yaml
# Task configuration level
abort:
  service: '@CleverAge\ProcessBundle\Task\StopTask'
```

Typically used in an error branch:

```yaml
validate:
  service: '@CleverAge\ProcessBundle\Task\Validation\ValidatorTask'
  options:
    error_output_violations: true
  errors: [abort]

abort:
  service: '@CleverAge\ProcessBundle\Task\StopTask'
```
