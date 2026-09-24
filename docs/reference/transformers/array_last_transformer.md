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

`any`: the last element of the array

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

Notes
-----

An empty array triggers an "Undefined array key" warning.
