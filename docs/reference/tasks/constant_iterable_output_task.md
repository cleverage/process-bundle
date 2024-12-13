ConstantIterableOutputTask
==========================

Same as ConstantOutputTask but only accepts an array of values and iterates over each element.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ConstantIterableOutputTask`

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`any`: iterate on the `output` option

Options
-------

| Code     | Type    | Required | Default | Description                     |
|----------|---------|:--------:|---------|---------------------------------|
| `output` | `array` |  **X**   |         | Array of values to iterate onto |

Example
-------

```yaml
# Task configuration level
code:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  options:
    output:
      id: 123
      firstname: Test1
      lastname: Test2
```
