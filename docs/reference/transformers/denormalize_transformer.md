DenormalizeTransformer
======================

Denormalize the input into an instance of the given class, using the Symfony
[Serializer](https://symfony.com/doc/current/components/serializer.html) denormalizer.

See also [DenormalizerTask](../tasks/denormalizer_task.md) for the task equivalent.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Serialization\DenormalizeTransformer`
* **Transformer code**: `denormalize`

Accepted inputs
---------------

`any`: data supported by the configured denormalizers (usually an `array`)

Possible outputs
----------------

`any`: the denormalized value, usually an instance of `class`

Options
-------

| Code      | Type           | Required | Default | Description                                                                                  |
|-----------|----------------|:--------:|---------|----------------------------------------------------------------------------------------------|
| `class`   | `string`       |  **X**   |         | Target type: a fully qualified class name, or e.g. `App\Entity\Author[]` for a collection    |
| `format`  | `string\|null` |          | `null`  | Format passed to the denormalizer                                                            |
| `context` | `array`        |          | `[]`    | Denormalization context (groups, `object_to_populate`…)                                      |

Examples
--------

* Denormalize an array into an `Author` entity

```yaml
# Transformer options level
denormalize:
  class: 'App\Entity\Author'
```

* Denormalize using serialization groups

```yaml
# Transformer options level
denormalize:
  class: 'App\Entity\Book'
  context:
    groups: [import]
```
