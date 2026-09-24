TypeSetterTransformer
=====================

Change the type of the input value using [`settype()`](https://www.php.net/manual/en/function.settype.php). Unlike
[CastTransformer](cast_transformer.md), the target type is validated when options are resolved.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\TypeSetterTransformer`
* **Transformer code**: `type_setter`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: the input value converted to the configured type.

Options
-------

| Code   | Type     | Required | Default | Description                                                                                                     |
|--------|----------|:--------:|---------|-----------------------------------------------------------------------------------------------------------------|
| `type` | `string` |  **X**   |         | Target type, one of `boolean`, `bool`, `integer`, `int`, `float`, `double`, `string`, `array`, `object`, `null` |

Examples
--------

```yaml
# Transformer options level
type_setter:
  type: string
```

Notes
-----

A `TransformerException` is thrown if `settype()` returns `false`.
