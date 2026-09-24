ArrayMapTransformer
===================

Apply a chain of transformers to each element of an iterable value. Keys are preserved.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayMapTransformer`
* **Transformer code**: `array_map`

Accepted inputs
---------------

`array` or `\Traversable`. Any other value throws an `\UnexpectedValueException`.

Possible outputs
----------------

`array`: the transformed elements, with their original keys

Options
-------

| Code           | Type    | Required | Default | Description                                                                                   |
|----------------|---------|:--------:|---------|-----------------------------------------------------------------------------------------------|
| `transformers` | `array` |  **X**   |         | Transformers applied to each element, see [TransformerTrait](../traits/transformer_trait.md)  |
| `skip_null`    | `bool`  |          | `false` | If `true`, elements whose transformed value is `null` are removed from the result             |

When a sub-transformer fails, the thrown `TransformerException` references the key of the failing element.

Examples
--------

* Cast each element to string, then uppercase it

```yaml
# Transformer mapping level
array_map:
  code:
    - '[id]'
    - '[firstname]'
    - '[lastname]'
  transformers:
    array_map:
      transformers:
        cast:
          type: 'string'
        callback:
          callback: strtoupper
```

* Convert each `stdClass` item into an array and remap it

```yaml
# Transformer options level
array_map:
  transformers:
    cast:
      type: 'array'
    mapping:
      mapping:
        isoCode:
          code: '[sISOCode]'
        name:
          code: '[sName]'
```
