DebugTask
=========

Dumps the input using the [VarDumper Component](https://symfony.com/doc/current/components/var_dumper.html), then
passes it to the output. If VarDumper is not installed, nothing is dumped and the input is simply forwarded.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Debug\DebugTask`

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

* Dump a constant value

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output:
      id: 123
      firstname: Test1
  outputs: [debug]
debug:
  service: '@CleverAge\ProcessBundle\Task\Debug\DebugTask'
```
