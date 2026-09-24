ConstantTransformer
===================

Always return the configured value, whatever the input. Inside a [MappingTransformer](mapping_transformer.md), the
`constant` property option usually does the same job; this transformer is useful elsewhere (e.g. in a
[TransformerTask](../tasks/transformer_task.md) or in a transformer chain).

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\ConstantTransformer`
* **Transformer code**: `constant`

Accepted inputs
---------------

`any`: the input is ignored.

Possible outputs
----------------

`any`: the configured constant.

Options
-------

| Code       | Type  | Required | Default | Description         |
|------------|-------|:--------:|---------|---------------------|
| `constant` | `any` |  **X**   |         | The value to return |

Examples
--------

```yaml
# Transformer options level
constant:
  constant: default_value
```
