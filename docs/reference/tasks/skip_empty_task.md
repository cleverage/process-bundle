SkipEmptyTask
=============

Passes the input to the output, but skips it if it is empty (PHP `empty()`: `null`, `false`, `0`, `'0'`, `''`, `[]`).
Useful after an aggregator task to avoid processing empty results.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\SkipEmptyTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: the input, unchanged, if it is not empty (the task is skipped otherwise)

Options
-------

This task has no option.

Examples
--------

* Only continue when the merged result is not empty

```yaml
# Task configuration level
merge:
  service: '@CleverAge\ProcessBundle\Task\ArrayMergeTask'
  outputs: [skip_if_empty]
skip_if_empty:
  service: '@CleverAge\ProcessBundle\Task\SkipEmptyTask'
  outputs: [next_task]
```
