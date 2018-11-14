ArrayFilterTransformer
======================

Filter data from an iterable value. Should match mostly native [array_filter](https://secure.php.net/manual/fr/function.array-filter.php) function behavior, as such array keys are preserved.

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
