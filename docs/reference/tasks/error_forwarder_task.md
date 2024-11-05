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

Example
-------

```yaml
clever_age_process:
  configurations:
    project_prefix.error_forwarder_example:
      tasks:
        error_forwarder_example:
          service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
          options:
            output:
              error1: Error 1
              error2: Error 2
              error3: Error 3
          outputs: [error_forwarder]
        error_forwarder:
          service: '@CleverAge\ProcessBundle\Task\Debug\ErrorForwarderTask'

```