DenormalizeTransformer
======================

Denormalize the input data into an object using Symfony's Denormalizer.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Serialization\DenormalizeTransformer`
* **Transformer code**: `denormalize`

Accepted inputs
---------------

`array`: data to denormalize

Possible outputs
----------------

`object`: an instance of the configured class, populated from the input data

Options
-------

| Code      | Type            | Required | Default | Description                                              |
|-----------|-----------------|:--------:|---------|----------------------------------------------------------|
| `class`   | `string`        | **X**    |         | Target class for denormalization                         |
| `format`  | `string\|null`  |          | `null`  | Format hint for the denormalizer                         |
| `context` | `array`         |          | `[]`    | Denormalization context                                  |

Examples
--------

```yaml
# Transformer options level
denormalize:
  class: 'App\Entity\Product'
  context:
    groups: [import]
```
