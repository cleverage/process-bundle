ConstantTransformer
===================

Always return the same configured value, ignoring the input. Useful inside a mapping to set a fixed value for a
property.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\ConstantTransformer`
* **Transformer code**: `constant`

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`any`: the configured constant value

Options
-------

| Code       | Type  | Required | Default | Description              |
|------------|-------|:--------:|---------|--------------------------|
| `constant` | `any` | **X**    |         | The value to return      |

Examples
--------

```yaml
# Transformer options level
constant:
  constant: default_value
```
