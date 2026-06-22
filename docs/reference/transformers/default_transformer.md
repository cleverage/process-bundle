DefaultTransformer
==================

Return a default value when the input is falsy (evaluated with PHP's loose boolean check). If the input is truthy,
it is returned unchanged.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\DefaultTransformer`
* **Transformer code**: `default`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: the input value if truthy, or the configured default value

Options
-------

| Code    | Type  | Required | Default | Description                                    |
|---------|-------|:--------:|---------|------------------------------------------------|
| `value` | `any` | **X**    |         | The default value to return when input is falsy |

Examples
--------

```yaml
# Transformer options level
default:
  value: N/A
```
