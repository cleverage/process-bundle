SkipEmptyTask
=============

Pass the input to the output, but skip the execution if the input is empty (using PHP's `empty()` check). Useful
when combined with an aggregator task to avoid processing empty results.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\SkipEmptyTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: same as input (skipped if input is empty)

Example
-------

```yaml
# Task configuration level
skip_if_empty:
  service: '@CleverAge\ProcessBundle\Task\SkipEmptyTask'
  outputs: [next_task]
```
