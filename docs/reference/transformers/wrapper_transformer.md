WrapperTransformer
==================

Wrap the input value into a single-element array, under a configurable key.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\WrapperTransformer`
* **Transformer code**: `wrapper`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`array`: `[<wrapper_key> => <input>]`

Options
-------

| Code          | Type          | Required | Default | Description                |
|---------------|---------------|:--------:|---------|----------------------------|
| `wrapper_key` | `string\|int` |          | `0`     | Key used to wrap the value |

Examples
--------

* The input `hello` becomes `{ data: hello }`

```yaml
# Transformer options level
wrapper:
  wrapper_key: data
```

* The input `hello` becomes `[hello]`

```yaml
# Transformer options level
wrapper: ~
```
