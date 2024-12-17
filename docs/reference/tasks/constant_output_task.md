ConstantOutputTask
==================

Simply outputs the same configured value all the time, ignores any input

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ConstantOutputTask`

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`any`: directly output given `output` option

Options
-------

| Code     | Type  | Required | Default | Description     |
|----------|-------|:---------|---------|-----------------|
| `output` | `any` | **X**    |         | Value to output |

Example
-------

```yaml
# Task configuration level
code:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output:
      id: 123
      firstname: Test1
      lastname: Test2
```
