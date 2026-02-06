SimpleBatchTask
===============

Accumulate inputs into batches of a configurable size. Once the batch is full, it is output as an array. Any
remaining items are flushed at the end of the iteration.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\SimpleBatchTask`
* **Flushable task**

Accepted inputs
---------------

`any`

Possible outputs
----------------

`array`: array of accumulated inputs, with up to `batch_count` elements

Options
-------

| Code          | Type      | Required | Default | Description                     |
|---------------|-----------|:--------:|---------|---------------------------------|
| `batch_count` | `integer` |          | `10`    | Number of items per batch       |

Example
-------

```yaml
# Task configuration level
batch:
  service: '@CleverAge\ProcessBundle\Task\SimpleBatchTask'
  options:
    batch_count: 50
  outputs: [process_batch]
```
