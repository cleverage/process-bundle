DummyTask
=========

Passes the input to the output without any change. Useful as an entry point to start several branches from the same
input, or as a placeholder / junction task.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\DummyTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: the input, unchanged

Options
-------

This task has no option.

Examples
--------

* Use as an entry point to run two branches

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\DummyTask'
  outputs: [output1, output2]
output1:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output: { id: 123 }
output2:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output: { id: 456 }
```
