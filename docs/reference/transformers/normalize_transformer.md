NormalizeTransformer
====================

Normalize the input (typically an object) into an array or a scalar, using the Symfony
[Serializer](https://symfony.com/doc/current/components/serializer.html) normalizer.

See also [NormalizerTask](../tasks/normalizer_task.md) for the task equivalent.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Serialization\NormalizeTransformer`
* **Transformer code**: `normalize`

Accepted inputs
---------------

`any`: data supported by the configured normalizers (typically an `object`)

Possible outputs
----------------

`array`, `string`, `int`, `float`, `bool`, `\ArrayObject` or `null`: the normalized representation

Options
-------

| Code      | Type           | Required | Default | Description                                         |
|-----------|----------------|:--------:|---------|-----------------------------------------------------|
| `format`  | `string\|null` |          | `null`  | Format passed to the normalizer                     |
| `context` | `array`        |          | `[]`    | Normalization context (groups, attributes…)         |

Examples
--------

* Normalize an object with default options

```yaml
# Transformer options level
normalize: ~
```

* Normalize an object using serialization groups

```yaml
# Transformer options level
normalize:
  context:
    groups: [export]
```
