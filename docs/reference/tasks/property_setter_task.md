PropertySetterTask
==================

Sets static values on the input (array or object) using property paths, then outputs the modified input.

See the [PropertyAccess component documentation](https://symfony.com/doc/current/components/property_access.html)
for the property path syntax.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\PropertySetterTask`

Accepted inputs
---------------

`array` or `object` writable by the property accessor at the given property paths.

Possible outputs
----------------

The input, with the configured values set.

If a value cannot be set, the exception is set on the state (with `property` and `value` added to the error context)
and handled according to the task `error_strategy`; the remaining values are not set. Note that only `string`, `int`
and `array` values can be added to the error context: for other value types (`bool`, `float`, `null`, objects), a
`\TypeError` is raised instead of the original exception (it is still handled according to `error_strategy`).

Options
-------

| Code     | Type    | Required | Default | Description                                         |
|----------|---------|:--------:|---------|-----------------------------------------------------|
| `values` | `array` |  **X**   |         | Map of `property path => value` to set on the input |

Examples
--------

* Change the first name of an entity

```yaml
# Task configuration level
modify:
  service: '@CleverAge\ProcessBundle\Task\PropertySetterTask'
  options:
    values:
      firstname: Gérard
  outputs: [dump_modified]
```

* Set keys on an array input

```yaml
# Task configuration level
set_defaults:
  service: '@CleverAge\ProcessBundle\Task\PropertySetterTask'
  options:
    values:
      '[status]': imported
      '[source]': '{{ source }}'
  outputs: [next_task]
```
