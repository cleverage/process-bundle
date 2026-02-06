ArrayUnsetTransformer
=====================

Remove a key from an array.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayUnsetTransformer`
* **Transformer code**: `array_unset`

Accepted inputs
---------------

`array`

Possible outputs
----------------

`array`: the input array without the specified key

Options
-------

| Code  | Type          | Required | Default | Description                 |
|-------|---------------|:--------:|---------|-----------------------------|
| `key` | `string\|int` | **X**    |         | The key to remove           |

Examples
--------

```yaml
# Transformer options level
array_unset:
  key: temporary_field
```
