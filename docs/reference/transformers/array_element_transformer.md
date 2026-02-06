ArrayElementTransformer
=======================

Return the nth element of an array (supports negative indices).

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayElementTransformer`
* **Transformer code**: `array_element`

Accepted inputs
---------------

`array`

Possible outputs
----------------

`any`: the element at the specified index

Options
-------

| Code    | Type      | Required | Default | Description                                                  |
|---------|-----------|:--------:|---------|--------------------------------------------------------------|
| `index` | `integer` | **X**    |         | Position of the element to extract (0-based, negative allowed) |

Examples
--------

```yaml
# Transformer options level
array_element:
  index: 2
```
