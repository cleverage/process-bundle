IterableBatchTask
=================

Buffers inputs and, every `batch_count` inputs, iterates over the buffer to output its elements one by one. Remaining
elements are output (one by one as well) when the task is flushed, at the end of the upstream iteration. It is mainly
an example task: it is not really useful as is, but its `processInput()` method can be overridden to customize how
each input is transformed before being buffered.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\IterableBatchTask`
* **Iterable task**
* **Flushable task**

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: each buffered input (as returned by `processInput()`, the input unchanged by default)

Options
-------

| Code          | Type  | Required | Default | Description                                 |
|---------------|-------|:--------:|---------|---------------------------------------------|
| `batch_count` | `int` |          | `10`    | Number of inputs to buffer before iterating |

Examples
--------

* Buffer iterated values by 2

```yaml
# Task configuration level
iterator:
  service: '@CleverAge\ProcessBundle\Task\InputIteratorTask'
  outputs: [batch]
batch:
  service: '@CleverAge\ProcessBundle\Task\IterableBatchTask'
  options:
    batch_count: 2
  outputs: [next_task]
```
