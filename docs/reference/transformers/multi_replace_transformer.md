MultiReplaceTransformer
=======================

Replace a list of substrings in a string, using PHP
[`str_replace()`](https://www.php.net/manual/en/function.str-replace.php) once per entry of the replacement map.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\MultiReplaceTransformer`
* **Transformer code**: `multi_replace`

Accepted inputs
---------------

Any value that can be cast to string.

Possible outputs
----------------

`string`: the input with all replacements applied. If `replace_mapping` is empty, the input is returned unchanged
(not cast).

Options
-------

| Code              | Type    | Required | Default | Description                                                                              |
|-------------------|---------|:--------:|---------|------------------------------------------------------------------------------------------|
| `replace_mapping` | `array` |  **X**   |         | Searched string as key, replacement as value. Entries are applied sequentially, in order |

Examples
--------

```yaml
# Transformer options level
multi_replace:
  replace_mapping:
    ' ': '!'
    'name': ''
```

```yaml
# Transformer mapping level
firstname:
  code: '[firstname]'
  transformers:
    multi_replace:
      replace_mapping:
        ' ': '-'
```
