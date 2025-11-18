ImplodeTransformer
=========================

Join array elements with a string

This transformer uses the php internal function: https://www.php.net/manual/en/function.implode.php

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\ImplodeTransformer`
* **Transformer code**: `implode`

Accepted inputs
---------------

`array`

Possible outputs
----------------

`string`

Options
-------

| Code        | Type     | Required | Default | Description |
|-------------|----------|:--------:|---------|-------------|
| `separator` | `string` |  **X**   | `|`     |             |

Examples
--------

```yaml
# Transformer mapping level
sprintf_multiple:
  code:
    - '[firstname]'
    - '[lastname]'
  transformers:
    implode:
      separator: '-'
```
