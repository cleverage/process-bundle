Cache addon
===========

Contains tasks and transformers to handle cache.

Activation
----------

Activated if cache pool `cleverage_process` is defined.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\ArrayFilterTransformer`
* **Transformer code**: `array_filter`

Accepted inputs
---------------

`array` or `\Iterable`

Possible outputs
----------------

`array` containing only filtered data

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `condition` | `array` | | `[]` | See [ConditionTrait](TODO) |
____
