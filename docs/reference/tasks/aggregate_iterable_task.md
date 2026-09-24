AggregateIterableTask
=====================

Collects every received input in a list, and outputs the whole list once all previous tasks are resolved (typically
at the end of an upstream iteration).

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\AggregateIterableTask`
* **Blocking task**

Accepted inputs
---------------

`any`

Possible outputs
----------------

`array`: list of all received inputs, in reception order. The task is skipped if no input was received.

Options
-------

This task has no option.

Examples
--------

* Aggregate iterated values: outputs `[1, 2, 3]`

```yaml
# Task configuration level
data:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  options:
    output: [1, 2, 3]
  outputs: [aggregate]
aggregate:
  service: '@CleverAge\ProcessBundle\Task\AggregateIterableTask'
```
