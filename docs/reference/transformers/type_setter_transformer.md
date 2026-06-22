TypeSetterTransformer
=====================

Change the PHP type of the input value using `settype()`. Similar to [CastTransformer](cast_transformer.md) but
with a restricted list of allowed types and explicit error handling.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\TypeSetterTransformer`
* **Transformer code**: `type_setter`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: the input value with its type changed

Options
-------

| Code   | Type     | Required | Default | Description                                                                                                     |
|--------|----------|:--------:|---------|-----------------------------------------------------------------------------------------------------------------|
| `type` | `string` | **X**    |         | Target type. Allowed: `boolean`, `bool`, `integer`, `int`, `float`, `double`, `string`, `array`, `object`, `null` |

Examples
--------

```yaml
# Transformer options level
type_setter:
  type: string
```
