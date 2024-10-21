IterableBatchTask
=================

Accumulate inputs and periodically flush them using iterations.
It's mainly an example task since it's not useful as-is, but the processInput method may allow custom overrides.

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

`any`: same type as input

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `batch_count` | `integer` | | `10` | Accumulated batch size |

