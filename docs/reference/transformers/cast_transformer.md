CastTransformer
===============

Cast the input value to a different PHP type using `settype()`.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\CastTransformer`
* **Transformer code**: `cast`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: the input value cast to the configured type

Options
-------

| Code   | Type     | Required | Default | Description                                                                   |
|--------|----------|:--------:|---------|-------------------------------------------------------------------------------|
| `type` | `string` | **X**    |         | Target PHP type (e.g. `int`, `string`, `bool`, `float`, `array`, `object`)    |

Examples
--------

```yaml
# Transformer options level
cast:
  type: int
```
