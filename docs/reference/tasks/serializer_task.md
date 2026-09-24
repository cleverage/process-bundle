SerializerTask
==============

Serializes the input into a string of the given format using the Symfony Serializer `SerializerInterface`.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Serialization\SerializerTask`

Accepted inputs
---------------

`mixed`: any data supported by the serializer for the given `format`.

Possible outputs
----------------

`string`: the serialized representation of the input.

Options
-------

| Code      | Type     | Required | Default | Description                                                    |
|-----------|----------|:--------:|---------|----------------------------------------------------------------|
| `format`  | `string` |  **X**   |         | Serialization format (`json`, `xml`, `csv`, ...)               |
| `context` | `array`  |          | `[]`    | Serialization context, passed as 3rd argument of `serialize()` |

Examples
--------

* Serialize the input as pretty-printed JSON

```yaml
# Task configuration level
serialize:
  service: '@CleverAge\ProcessBundle\Task\Serialization\SerializerTask'
  options:
    format: json
    context:
      json_encode_options: !php/const JSON_PRETTY_PRINT
  outputs: [write]
```
