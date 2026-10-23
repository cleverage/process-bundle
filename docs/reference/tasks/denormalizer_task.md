DenormalizerTask
================

Denormalizes the input (usually an array) into an object of the configured class, using the Symfony Serializer
`DenormalizerInterface`.

Requires the Symfony serializer (`framework.serializer`): without it, the task fails with an explicit message.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask`
* **Code**: `denormalizer` (`task: denormalizer`)

Accepted inputs
---------------

`mixed`: any data supported by the configured denormalizers for the given `class` (typically an `array`).

Possible outputs
----------------

`mixed`: result of `DenormalizerInterface::denormalize()`, usually an instance of `class` (or an array of instances when
`class` ends with `[]`).

Options
-------

| Code      | Type           | Required | Default | Description                                                                  |
|-----------|----------------|:--------:|---------|------------------------------------------------------------------------------|
| `class`   | `string`       |  **X**   |         | Target type of the denormalization (FQCN, or `FQCN[]` for a list of objects) |
| `format`  | `string\|null` |          | `null`  | Format passed to the denormalizer (`json`, `xml`, ...)                       |
| `context` | `array`        |          | `[]`    | Denormalization context, passed as 4th argument of `denormalize()`           |

Examples
--------

* Denormalize an array into an entity

```yaml
# Task configuration level
denormalize:
  task: denormalizer
  options:
    class: App\Entity\Author
  outputs: [save]
```

* Denormalize a decoded JSON list into an array of DTOs

```yaml
# Task configuration level
dto:
  task: denormalizer
  options:
    class: 'App\Dto\Commune[]'
  outputs: [debug]
```
