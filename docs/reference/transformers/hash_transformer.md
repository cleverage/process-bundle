HashTransformer
===============

Generate a hash of the input value using PHP's `hash()` function.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\HashTransformer`
* **Transformer code**: `hash`

Accepted inputs
---------------

`string`: value to hash

Possible outputs
----------------

`string`: the hash value (hexadecimal by default, raw binary if `raw_output` is `true`)

Options
-------

| Code         | Type      | Required | Default | Description                                                        |
|--------------|-----------|:--------:|---------|--------------------------------------------------------------------|
| `algo`       | `string`  | **X**    |         | Hash algorithm (e.g. `md5`, `sha256`, `crc32`). See `hash_algos()` |
| `raw_output` | `boolean` |          | `false` | If `true`, output raw binary data instead of hexadecimal           |

Examples
--------

```yaml
# Transformer options level
hash:
  algo: sha256
```
