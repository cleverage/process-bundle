ArrayFirstTransformer
=====================

Return the first element of an array.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayFirstTransformer`
* **Transformer code**: `array_first`

Accepted inputs
---------------

`array` or `iterable`

Possible outputs
----------------

`any`: the first element of the input. If the input is not iterable and `allow_not_iterable` is `false`, the
value is returned as-is.

Options
-------

| Code                 | Type   | Required | Default | Description                                                           |
|----------------------|--------|:--------:|---------|-----------------------------------------------------------------------|
| `allow_not_iterable` | `bool` |          | `false` | If `false`, non-iterable values are returned unchanged                |

Examples
--------

```yaml
# Transformer options level
array_first: ~
```
