ArrayUnsetTransformer
=====================

Remove a key from an array.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayUnsetTransformer`
* **Transformer code**: `array_unset`

Accepted inputs
---------------

`array`. Any other value throws an `\UnexpectedValueException`.

Possible outputs
----------------

`array`: the input array without the given key (unchanged if the key does not exist)

Options
-------

| Code  | Type          | Required | Default | Description        |
|-------|---------------|:--------:|---------|--------------------|
| `key` | `string\|int` |  **X**   |         | The key to remove  |

Examples
--------

* `{id: 1, temporary_field: 'foo'}` becomes `{id: 1}`

```yaml
# Transformer options level
array_unset:
  key: temporary_field
```
