ArrayLastTransformer
====================

Return the last element of an array, using `array_slice()`.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayLastTransformer`
* **Transformer code**: `array_last`

Accepted inputs
---------------

`array`

Possible outputs
----------------

`any`: the last element of the array, `null` for an empty array

Options
-------

This transformer has no option.

Examples
--------

* `['foo', 'bar', 'baz']` becomes `'baz'`

```yaml
# Transformer options level
array_last: ~
```
