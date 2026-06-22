SerializerTask
==============

Serialize the input data into a given format using Symfony's Serializer component.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Serialization\SerializerTask`

Accepted inputs
---------------

`any`: data that can be serialized by Symfony's Serializer

Possible outputs
----------------

`string`: the serialized representation of the input in the given format

Options
-------

| Code      | Type    | Required | Default | Description                                                    |
|-----------|---------|:--------:|---------|----------------------------------------------------------------|
| `format`  | `string`| **X**    |         | Serialization format (e.g. `json`, `xml`, `csv`)               |
| `context` | `array` |          | `[]`    | Serialization context passed to the serializer                 |

Example
-------

```yaml
# Task configuration level
serialize_to_json:
  service: '@CleverAge\ProcessBundle\Task\Serialization\SerializerTask'
  options:
    format: json
    context:
      json_encode_options: !php/const JSON_PRETTY_PRINT
```
