ConstantOutputTask
==================

Always outputs the same configured value, regardless of the input. Commonly used as an entry point to feed a
process with static data.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ConstantOutputTask`

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`any`: the value of the `output` option, as is

Options
-------

| Code     | Type  | Required | Default | Description     |
|----------|-------|:--------:|---------|-----------------|
| `output` | `any` |  **X**   |         | Value to output |

Examples
--------

* Output a static array, then dump it

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output:
      id: 123
      firstname: Test1
      lastname: Test2
  outputs: [debug]
```
