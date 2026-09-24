InputIteratorTask
=================

Iterates over the input and outputs each value one by one.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\InputIteratorTask`
* **Iterable task**

Accepted inputs
---------------

`array`, `\Iterator` or `\IteratorAggregate`: any other type throws an `\UnexpectedValueException`

Possible outputs
----------------

`any`: each value of the input (keys are not transmitted). If the input is empty, the task is skipped.

Options
-------

This task has no option.

Examples
--------

* Aggregate a list, then iterate over it again

```yaml
# Task configuration level
aggregate:
  service: '@CleverAge\ProcessBundle\Task\AggregateIterableTask'
  outputs: [iterate]
iterate:
  service: '@CleverAge\ProcessBundle\Task\InputIteratorTask'
  outputs: [next_task]
```
