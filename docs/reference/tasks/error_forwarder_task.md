ErrorForwarderTask
==================

Forwards any input to the error output and skips the normal output. Mostly intended for testing purposes (e.g. to
test error branches).

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Debug\ErrorForwarderTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

None on the normal output (the task is always skipped).

Error output: `any`, the input, unchanged

Options
-------

This task has no option.

Examples
--------

* Send every item to an error branch

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  options:
    output: [Error 1, Error 2, Error 3]
  outputs: [error_forwarder]
error_forwarder:
  service: '@CleverAge\ProcessBundle\Task\Debug\ErrorForwarderTask'
  error_outputs: [debug]
debug:
  service: '@CleverAge\ProcessBundle\Task\Debug\DebugTask'
```
