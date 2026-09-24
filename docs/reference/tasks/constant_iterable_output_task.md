ConstantIterableOutputTask
==========================

Same as [ConstantOutputTask](constant_output_task.md), but the `output` option must be an array: the task iterates
over it and outputs each value one by one, regardless of the input.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ConstantIterableOutputTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`any`: each value of the `output` array (keys are not transmitted). If the array is empty, the task is skipped.

Options
-------

| Code     | Type    | Required | Default | Description                     |
|----------|---------|:--------:|---------|---------------------------------|
| `output` | `array` |  **X**   |         | Array of values to iterate over |

Examples
--------

* Iterate over a static list: `123`, `Test1` and `Test2` are sent one after the other to `debug`

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  options:
    output:
      id: 123
      firstname: Test1
      lastname: Test2
  outputs: [debug]
```
