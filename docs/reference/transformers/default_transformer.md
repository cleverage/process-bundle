DefaultTransformer
==================

Return a default value when the input is falsy (PHP `!$value`: `null`, `false`, `0`, `0.0`, `''`, `'0'`, `[]`),
otherwise return the input unchanged.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\DefaultTransformer`
* **Transformer code**: `default`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: the input if truthy, the configured `value` otherwise.

Options
-------

| Code    | Type  | Required | Default | Description                            |
|---------|-------|:--------:|---------|----------------------------------------|
| `value` | `any` |  **X**   |         | Value returned when the input is falsy |

Examples
--------

```yaml
# Transformer options level
default:
  value: N/A
```

* Fallback after a conversion

```yaml
# Transformer mapping level
type:
  code: '[Type]'
  transformers:
    convert_value:
      ignore_missing: true
      map:
        TEXTE: text
    default:
      value: unknown
```

Notes
-----

As the check is loose, legitimate values such as `0` or `'0'` are also replaced by the default value.
