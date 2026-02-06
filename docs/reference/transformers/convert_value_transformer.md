ConvertValueTransformer
=======================

Transform a value into another value based on a conversion map (lookup table). If the value is not found in the
map, the behavior depends on the `ignore_missing` and `keep_missing` options.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\ConvertValueTransformer`
* **Transformer code**: `convert_value`

Accepted inputs
---------------

`string`, `int`, or `null`: a value that can be used as an array key for lookup

Possible outputs
----------------

`any`: the mapped value from the conversion table

Options
-------

| Code             | Type      | Required | Default | Description                                                                       |
|------------------|-----------|:--------:|---------|-----------------------------------------------------------------------------------|
| `map`            | `array`   | **X**    |         | Associative array mapping input values to output values                           |
| `ignore_missing` | `boolean` |          | `false` | If `true`, return `null` when the value is not found in the map                   |
| `keep_missing`   | `boolean` |          | `false` | If `true`, return the original value when not found (takes precedence on ignore)  |
| `auto_cast`      | `boolean` |          | `false` | If `true`, cast non-string/int values to string before lookup                     |

Examples
--------

* Simple value conversion

```yaml
# Transformer options level
convert_value:
  map:
    TEXTE: text
    NUMERIQUE: number
    DATE: date
  ignore_missing: true
```

* Keep original value when not found

```yaml
# Transformer options level
convert_value:
  map:
    old_code: new_code
  keep_missing: true
```
