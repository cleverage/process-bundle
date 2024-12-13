DummyTask
=========

Passes the input to the output, can be used as an entry point allow multiple tasks to be run at the entry point

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\DummyTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: re-output given input

Options
-------

None

Example
-------

```yaml
# Task configuration level
dummy:
  service: '@CleverAge\ProcessBundle\Task\DummyTask'
  outputs: [output1, output2]
output1:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output:
      id: 123
      firstname: Test1
      lastname: Test2
output2:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output:
      id: 456
      firstname: Test3
      lastname: Test4
```
