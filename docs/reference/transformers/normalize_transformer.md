NormalizeTransformer
====================

Normalize the input value (typically an object) into an array or scalar using Symfony's Normalizer.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Serialization\NormalizeTransformer`
* **Transformer code**: `normalize`

Accepted inputs
---------------

`any`: data to normalize (typically an object)

Possible outputs
----------------

`array|scalar`: the normalized representation

Options
-------

| Code      | Type            | Required | Default | Description                             |
|-----------|-----------------|:--------:|---------|-----------------------------------------|
| `format`  | `string\|null`  |          | `null`  | Format hint for the normalizer          |
| `context` | `array`         |          | `[]`    | Normalization context                   |

Examples
--------

```yaml
# Transformer options level
normalize:
  context:
    groups: [export]
```
