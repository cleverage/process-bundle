TransformerTask
===============

Passes the input through a chain of transformers and outputs the result. This is the main way to use transformers
inside a process.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\TransformerTask`

Accepted inputs
---------------

`mixed`: it must match the expected input of the first transformer of the chain.

Possible outputs
----------------

`mixed`: result of the last transformer of the chain. With an empty `transformers` list, the input is output unchanged.

If a transformer fails, the resulting `TransformerException` is set on the state (with the original error message
added to the error context as `error`) and handled according to the task `error_strategy`.

Options
-------

| Code           | Type    | Required | Default | Description                                                                                          |
|----------------|---------|:--------:|---------|------------------------------------------------------------------------------------------------------|
| `transformers` | `array` |          | `[]`    | Ordered map of `transformer code => options`, see [TransformerTrait](../traits/transformer_trait.md) |

Examples
--------

* Decode a JSON string into an associative array

```yaml
# Task configuration level
json_decode:
  service: '@CleverAge\ProcessBundle\Task\TransformerTask'
  options:
    transformers:
      callback:
        callback: json_decode
        right_parameters: [true]
  outputs: [dto]
```

* Map an array to a new structure, chaining the same transformer twice with the `#` suffix

```yaml
# Task configuration level
transform:
  service: '@CleverAge\ProcessBundle\Task\TransformerTask'
  options:
    transformers:
      mapping:
        mapping:
          name:
            code: '[firstname]'
            transformers:
              trim: ~
      callback#1:
        callback: array_filter
      callback#2:
        callback: array_reverse
  outputs: [load]
```

See [MappingTransformer](../transformers/mapping_transformer.md) and
[generic transformers](../03-generic_transformers_definition.md).
