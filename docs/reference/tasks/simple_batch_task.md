SimpleBatchTask
===============

Buffers inputs and outputs them as an array every `batch_count` inputs (the task is skipped the rest of the time).
Remaining items are output when the task is flushed, at the end of the upstream iteration.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\SimpleBatchTask`
* **Flushable task**

Accepted inputs
---------------

`any`

Possible outputs
----------------

`array`: list of buffered inputs, with at most `batch_count` elements. On flush, the task is skipped if the buffer is
empty.

Options
-------

| Code          | Type        | Required | Default | Description                                                             |
|---------------|-------------|:--------:|---------|-------------------------------------------------------------------------|
| `batch_count` | `int\|null` |          | `10`    | Batch size; if `null`, all inputs are buffered and only output on flush |

Examples
--------

* Group iterated values by 2: outputs `[1, 2]`, then `[3]` on flush

```yaml
# Task configuration level
data:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  options:
    output: [1, 2, 3]
  outputs: [batch]
batch:
  service: '@CleverAge\ProcessBundle\Task\SimpleBatchTask'
  options:
    batch_count: 2
  outputs: [next_task]
```
