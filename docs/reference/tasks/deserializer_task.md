DeserializerTask
================

Deserialize a string input into a PHP object or data structure using Symfony's Serializer component.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Serialization\DeserializerTask`

Accepted inputs
---------------

`string`: the serialized data to deserialize

Possible outputs
----------------

`any`: the deserialized object or data structure, matching the configured `type`

Options
-------

| Code      | Type     | Required | Default | Description                                                       |
|-----------|----------|:--------:|---------|-------------------------------------------------------------------|
| `type`    | `string` | **X**    |         | The target class or type to deserialize into (e.g. `App\Entity\Product`) |
| `format`  | `string` | **X**    |         | Deserialization format (e.g. `json`, `xml`, `csv`)                |
| `context` | `array`  |          | `[]`    | Deserialization context passed to the serializer                  |

Example
-------

```yaml
# Task configuration level
deserialize_json:
  service: '@CleverAge\ProcessBundle\Task\Serialization\DeserializerTask'
  options:
    type: 'App\Entity\Product'
    format: json
```
