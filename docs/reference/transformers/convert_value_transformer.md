ConvertValueTransformer
=======================

Convert a value into another one using a conversion map (lookup table).

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\ConvertValueTransformer`
* **Transformer code**: `convert_value`

Accepted inputs
---------------

* `string` or `int`: used as a key of `map`
* `null`: always returns `null`, without lookup
* other scalars or objects: only if `auto_cast` is `true` (cast to string), otherwise an `UnexpectedValueException` is
  thrown. Arrays are always rejected.

Possible outputs
----------------

`any`: the value matching the input in `map`, the input itself (`keep_missing`) or `null` (`ignore_missing`).

Options
-------

| Code             | Type    | Required | Default | Description                                                                                                |
|------------------|---------|:--------:|---------|------------------------------------------------------------------------------------------------------------|
| `map`            | `array` |  **X**   |         | Conversion table: input value as key, output value as value                                                |
| `ignore_missing` | `bool`  |          | `false` | If `true`, return `null` when the value is not in `map`, instead of throwing an `UnexpectedValueException` |
| `keep_missing`   | `bool`  |          | `false` | If `true`, return the input when the value is not in `map` (takes precedence on `ignore_missing`)          |
| `auto_cast`      | `bool`  |          | `false` | If `true`, cast input values that are not valid array keys (`float`, `bool`, `Stringable`...) to string    |

Examples
--------

* Simple conversion, unknown values become `null`

```yaml
# Transformer options level
convert_value:
  map:
    TEXTE: text
    NUMERIQUE: number
    DATE: date
  ignore_missing: true
```

* Keep the original value when it is not in the map

```yaml
# Transformer options level
convert_value:
  map:
    old_code: new_code
  keep_missing: true
```
