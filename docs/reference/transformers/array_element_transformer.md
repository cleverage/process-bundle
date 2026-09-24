ArrayElementTransformer
=======================

Return the element at a given position of an array, using `array_slice()`. The position is based on the order of the
elements, not on their keys, so it also works with associative arrays. Negative indexes are counted from the end.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayElementTransformer`
* **Transformer code**: `array_element`

Accepted inputs
---------------

`array`

Possible outputs
----------------

`any`: the element found at the given position

Options
-------

| Code    | Type  | Required | Default | Description                                                                 |
|---------|-------|:--------:|---------|-----------------------------------------------------------------------------|
| `index` | `int` |  **X**   |         | Position of the element (0-based, a negative value starts from the end)     |

Examples
--------

* Get the 2nd element: `['foo', 'bar', 'baz']` becomes `'bar'`

```yaml
# Transformer options level
array_element:
  index: 1
```

* Get the penultimate element: `['foo', 'bar', 'baz']` becomes `'bar'`

```yaml
# Transformer options level
array_element:
  index: -2
```

Notes
-----

There is no bound check: an index outside the array triggers an "Undefined array key" warning.
