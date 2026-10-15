ErrorForwarderTask
==================

Forwards any input to the error output and skips the normal output. Mostly intended for testing purposes (e.g. to
test error branches).

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Debug\ErrorForwarderTask`
* **Code**: `error_forwarder` (`task: error_forwarder`)

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
  task: constant_iterable_output
  options:
    output: [Error 1, Error 2, Error 3]
  outputs: [error_forwarder]
error_forwarder:
  task: error_forwarder
  error_outputs: [debug]
debug:
  task: debug
```
