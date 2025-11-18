ArrayMapTransformer
=========================

Applies transformers to each element of an array.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayMapTransformer`
* **Transformer code**: `array_map`

Accepted inputs
---------------

`array`

Possible outputs
----------------

`string`

Options
-------

| Code           | Type    | Required | Default | Description                                                                  |
|----------------|---------|:--------:|---------|------------------------------------------------------------------------------|
| `transformers` | `array` |  **X**   |         | List of transformers, see [TransformerTrait](../traits/transformer_trait.md) |
| `skip_null`    | `bool`  |          | `false` | If true continue without applying other transformers on null values          |


Examples
--------

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
        uppercase: ~
```
