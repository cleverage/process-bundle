FilterTask
==========

Passes the input to the output only if it matches all the configured conditions. Otherwise, the input is sent to the
error output and the task is skipped.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\FilterTask`

Accepted inputs
---------------

`array` or `object`: values are read with the Symfony PropertyAccessor. A non-readable property is considered `null`.

Possible outputs
----------------

`any`: the input, unchanged, when all conditions match.

Error output: the input, unchanged, when a condition does not match.

Options
-------

Condition options are provided by [ConditionTrait](../traits/condition_trait.md), directly at the root of the task
options. Each option is a map of property path => value; all conditions must be satisfied.

| Code               | Type    | Required | Default | Description                                                               |
|--------------------|---------|:--------:|---------|---------------------------------------------------------------------------|
| `match`            | `array` |          | `[]`    | Property path => value: the property must be strictly equal (`===`) to it |
| `not_match`        | `array` |          | `[]`    | Property path => value: the property must not be strictly equal to it     |
| `empty`            | `array` |          | `[]`    | Property path => (ignored): the property must be empty (PHP `empty()`)    |
| `not_empty`        | `array` |          | `[]`    | Property path => (ignored): the property must not be empty                |
| `match_regexp`     | `array` |          | `[]`    | Property path => regular expression: the property must match it           |
| `not_match_regexp` | `array` |          | `[]`    | Property path => regular expression: the property must not match it       |

Examples
--------

* Keep only active items, send the others to an error branch

```yaml
# Task configuration level
filter_active:
  service: '@CleverAge\ProcessBundle\Task\FilterTask'
  options:
    match:
      '[status]': active
    not_empty:
      '[sku]': ~
  outputs: [next_task]
  error_outputs: [handle_inactive]
```
