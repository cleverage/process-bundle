DeserializerTask
================

Deserializes a string input into a PHP value (object, array...) using the Symfony Serializer `SerializerInterface`.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Serialization\DeserializerTask`

Accepted inputs
---------------

`string`: the serialized data, in the configured `format`.

Possible outputs
----------------

`mixed`: result of `SerializerInterface::deserialize()`, matching the configured `type`.

Options
-------

| Code      | Type     | Required | Default | Description                                                        |
|-----------|----------|:--------:|---------|--------------------------------------------------------------------|
| `type`    | `string` |  **X**   |         | Target type of the deserialization (FQCN, `FQCN[]`, ...)           |
| `format`  | `string` |  **X**   |         | Format of the input data (`json`, `xml`, `csv`, ...)               |
| `context` | `array`  |          | `[]`    | Deserialization context, passed as 4th argument of `deserialize()` |

Examples
--------

* Deserialize a JSON string into an entity

```yaml
# Task configuration level
deserialize:
  service: '@CleverAge\ProcessBundle\Task\Serialization\DeserializerTask'
  options:
    type: App\Entity\Author
    format: json
  outputs: [save]
```
