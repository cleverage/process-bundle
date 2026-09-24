HashTransformer
===============

Generate a hash of the input value, using PHP's [hash](https://www.php.net/manual/en/function.hash.php) function.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\HashTransformer`
* **Transformer code**: `hash`

Accepted inputs
---------------

Any value that can be cast to `string`.

Possible outputs
----------------

`string`: the hash, as lowercase hexits by default, or raw binary data if `raw_output` is `true`

Options
-------

| Code         | Type     | Required | Default | Description                                                                                   |
|--------------|----------|:--------:|---------|-----------------------------------------------------------------------------------------------|
| `algo`       | `string` |  **X**   |         | Hashing algorithm (e.g. `md5`, `sha1`, `sha256`, `crc32b`), must be one of `hash_algos()`     |
| `raw_output` | `bool`   |          | `false` | If `true`, output raw binary data instead of lowercase hexits                                 |

Examples
--------

* `'foo'` becomes `'2c26b46b68ffc68ff99b453c1d30413413422d706483bfa0f98a5e886266e7ae'`

```yaml
# Transformer options level
hash:
  algo: sha256
```

* Compute a checksum of several fields

```yaml
# Transformer mapping level
checksum:
  code:
    - '[sku]'
    - '[price]'
  transformers:
    implode:
      separator: '|'
    hash:
      algo: md5
```
