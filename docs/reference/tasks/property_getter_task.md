PropertyGetterTask
==================

Reads a value from the input (array or object) using a property path, and outputs it.

See the [PropertyAccess component documentation](https://symfony.com/doc/current/components/property_access.html)
for the property path syntax.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\PropertyGetterTask`

Accepted inputs
---------------

`array` or `object` readable by the property accessor at the given `property` path.

Possible outputs
----------------

`mixed`: the value read at the `property` path.

If the value cannot be read, the exception is set on the state (with the `property` added to the error context) and
handled according to the task `error_strategy`.

Options
-------

| Code       | Type     | Required | Default | Description                          |
|------------|----------|:--------:|---------|--------------------------------------|
| `property` | `string` |  **X**   |         | Property path to read from the input |

Examples
--------

* Extract the `path` of each file listed by a Flysystem task

```yaml
# Task configuration level
get_file_path:
  service: '@CleverAge\ProcessBundle\Task\PropertyGetterTask'
  options:
    property: 'path'
  outputs: [remove_input]
```

* Read a key from an array input

```yaml
# Task configuration level
get_sku:
  service: '@CleverAge\ProcessBundle\Task\PropertyGetterTask'
  options:
    property: '[sku]'
  outputs: [next_task]
```
