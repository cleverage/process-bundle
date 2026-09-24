CastTransformer
===============

Cast the input value to another PHP type using [`settype()`](https://www.php.net/manual/en/function.settype.php).

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\CastTransformer`
* **Transformer code**: `cast`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: the input value converted to the configured type.

Options
-------

| Code   | Type     | Required | Default | Description                                                                                                                                  |
|--------|----------|:--------:|---------|----------------------------------------------------------------------------------------------------------------------------------------------|
| `type` | `string` |  **X**   |         | Target type, any value accepted by `settype()` (`bool`, `boolean`, `int`, `integer`, `float`, `double`, `string`, `array`, `object`, `null`) |

Examples
--------

* Cast a string to an integer

```yaml
# Transformer options level
cast:
  type: int
```

* Convert a `stdClass` (e.g. a SOAP response item) to an array

```yaml
# Transformer options level
cast:
  type: array
```

Notes
-----

The `type` value is not validated at configuration time: an invalid type throws a `ValueError` on transformation. See
[TypeSetterTransformer](type_setter_transformer.md) for a variant validating the type upfront.
