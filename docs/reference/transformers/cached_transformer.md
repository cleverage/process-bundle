CachedTransformer
=================

Wrap a chain of transformers with a PSR-6 cache layer. A cache key is built from the input value; if the cache
contains an item for this key it is returned directly, otherwise the wrapped transformers are applied and the result
is stored in cache.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\CachedTransformer`
* **Transformer code**: `cached`

Accepted inputs
---------------

`string`: the value is used both to build the cache key (after `key_transformers`) and as input of `transformers`.
Any other type raises a `TypeError`.

Possible outputs
----------------

`any`: result of the `transformers` chain, or the cached value on cache hit.

Options
-------

| Code               | Type                              | Required | Default | Description                                                                                                                     |
|--------------------|-----------------------------------|:--------:|---------|---------------------------------------------------------------------------------------------------------------------------------|
| `cache_key`        | `string`                          |  **X**   |         | Root of the cache key. The final key is `<cache_key>\|<rawurlencode(key value)>`                                                |
| `ttl`              | `string\|DateTimeInterface\|null` |          | `null`  | Expiration date of the cache items. A string is converted with `new \DateTime($value)` (e.g. `+1 hour`). `null` means no expiry |
| `transformers`     | `array`                           |          | `[]`    | Transformers applied on cache miss, see [TransformerTrait](../traits/transformer_trait.md)                                      |
| `key_transformers` | `array`                           |          | `[]`    | Transformers applied on the input to compute the key value, see [TransformerTrait](../traits/transformer_trait.md)              |

Examples
--------

* Cache a costly transformation for one hour, using a slugified input as key

```yaml
# Transformer options level
cached:
  cache_key: my_prefix
  ttl: '+1 hour'
  key_transformers:
    slugify: ~
  transformers:
    callback:
      callback: md5
```

Notes
-----

* The cache pool is the autowired `Psr\Cache\CacheItemPoolInterface` service (`cache.app` in a standard Symfony
  application). Items are saved with `saveDeferred()`, a warning is logged if the save fails.
* A string `ttl` is converted to a date when the options are resolved (i.e. once, when the transformer is configured),
  not each time an item is saved: all items share the same absolute expiration date.
* If the key value is not a string after `key_transformers`, or if the cache pool raises a PSR-6
  `InvalidArgumentException` (logged as a warning), the transformers are applied without cache.
