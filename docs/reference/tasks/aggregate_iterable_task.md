AggregateIterableTask
=====================

Collects every received input in a list, and outputs the whole list once all previous tasks are resolved (typically
at the end of an upstream iteration).

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\AggregateIterableTask`
* **Code**: `aggregate_iterable` (`task: aggregate_iterable`)
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
  task: constant_iterable_output
  options:
    output: [1, 2, 3]
  outputs: [aggregate]
aggregate:
  task: aggregate_iterable
```
