CachedTransformer
=================

Wrap a chain of transformers with a PSR-6 cache layer. If the cache contains a result for the generated key, it is
returned directly. Otherwise, the transformers are applied and the result is stored in cache.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\CachedTransformer`
* **Transformer code**: `cached`

Accepted inputs
---------------

`string`: value used to generate the cache key (via `key_transformers`)

Possible outputs
----------------

`any`: result of the wrapped transformers chain (or cached value)

Options
-------

| Code               | Type                          | Required | Default | Description                                                                                   |
|--------------------|-------------------------------|:--------:|---------|-----------------------------------------------------------------------------------------------|
| `cache_key`        | `string`                      | **X**    |         | Root cache key prefix                                                                         |
| `ttl`              | `string\|DateTimeInterface\|null` |      | `null`  | Cache expiration. A string is parsed as a DateTime (e.g. `+1 hour`). `null` means no expiry. |
| `transformers`     | `array`                       |          | `[]`    | Transformers to apply on cache miss, see [TransformerTrait](../traits/transformer_trait.md)   |
| `key_transformers` | `array`                       |          | `[]`    | Transformers to apply on the value to generate the cache key                                  |

Examples
--------

```yaml
# Transformer options level
cached:
  cache_key: my_prefix
  ttl: '+1 hour'
  key_transformers:
    slugify: ~
  transformers:
    mapping:
      mapping:
        name:
          code: '[name]'
```
