WrapperTransformer
==================

Wrap the input value into a single-element array with a configurable key.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\WrapperTransformer`
* **Transformer code**: `wrapper`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`array`: `[wrapper_key => value]`

Options
-------

| Code          | Type          | Required | Default | Description                         |
|---------------|---------------|:--------:|---------|-------------------------------------|
| `wrapper_key` | `string\|int` |          | `0`     | Key used to wrap the value          |

Examples
--------

```yaml
# Transformer options level
wrapper:
  wrapper_key: data
```

Input `"hello"` becomes `{data: "hello"}`.
