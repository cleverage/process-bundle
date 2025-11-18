MultiReplaceTransformer
=========================

Quickly replace a list of values in a string.

This transformer uses the php internal function: https://www.php.net/manual/en/function.str-replace.php

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\MultiReplaceTransformer`
* **Transformer code**: `multi_replace`

Accepted inputs
---------------

Any value that can be cast to string.

Possible outputs
----------------

`string`

Options
-------

| Code              | Type    | Required | Default | Description                       |
|-------------------|---------|:--------:|---------|-----------------------------------|
| `replace_mapping` | `array` |  **X**   |         | $search as key, $replace as value |

Examples
--------

```yaml
# Transformer mapping level
multi_replace:
  code:
    - '[firstname]'
  transformers:
    multi_replace:
    replace_mapping:
      ' ': '!'
      'name': ''
```
