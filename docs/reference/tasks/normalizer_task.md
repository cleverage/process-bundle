NormalizerTask
==============

Normalizes the input (usually an object) using the Symfony Serializer `NormalizerInterface`.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Serialization\NormalizerTask`

Accepted inputs
---------------

`mixed`: any value supported by the normalizers for the given `format`. If no normalizer supports it, an
`\UnexpectedValueException` is thrown.

Possible outputs
----------------

`array|string|int|float|bool|\ArrayObject|null`: result of `NormalizerInterface::normalize()` (usually an `array`).

Options
-------

| Code      | Type     | Required | Default | Description                                                    |
|-----------|----------|:--------:|---------|----------------------------------------------------------------|
| `format`  | `string` |  **X**   |         | Format passed to the normalizer (`json`, `xml`, ...)           |
| `context` | `array`  |          | `[]`    | Normalization context, passed as 3rd argument of `normalize()` |

Examples
--------

* Normalize entities read from Doctrine into arrays, restricted to a serialization group

```yaml
# Task configuration level
normalize:
  service: '@CleverAge\ProcessBundle\Task\Serialization\NormalizerTask'
  options:
    format: json
    context:
      groups: [export]
  outputs: [write]
```
