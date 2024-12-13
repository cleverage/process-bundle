CounterTask
==================

This is a dummy task mostly intended for testing purpose.

Forward any input to the error output.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Debug\ErrorForwarderTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: directly error_output given `output` option

Options
-------

None

Example
-------

```yaml
# Task configuration level
code:
  service: '@CleverAge\ProcessBundle\Task\Debug\ErrorForwarderTask'
```
