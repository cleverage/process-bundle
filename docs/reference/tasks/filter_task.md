FilterTask
==========

Skip inputs that do not match given conditions. When the condition is not met, the input is sent to the error
output and the task is skipped.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\FilterTask`

Accepted inputs
---------------

`any`: value to check against the condition

Possible outputs
----------------

`any`: same as input if the condition is met

On mismatch, the input is sent to error output and the task is skipped.

Options
-------

Options are provided by [ConditionTrait](../traits/condition_trait.md). Equality is softly checked, and a
non-existing key is treated as `null`.

Example
-------

```yaml
# Task configuration level
filter_active:
  service: '@CleverAge\ProcessBundle\Task\FilterTask'
  options:
    match:
      '[status]': active
  errors: [handle_inactive]
```
