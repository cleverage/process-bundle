DebugTask
=========

Dumps the input value to the console, obviously for debug purposes.
Only usable in dev environment (where the [VarDumper Component](https://symfony.com/doc/current/components/var_dumper.html) is enabled)


Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Debug\DebugTask`

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
----------------

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
