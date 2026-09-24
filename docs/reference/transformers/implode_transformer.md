ImplodeTransformer
==================

Join the elements of an array into a string, using PHP's [implode](https://www.php.net/manual/en/function.implode.php)
function.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\ImplodeTransformer`
* **Transformer code**: `implode`

Accepted inputs
---------------

`array` of values that can be cast to `string`. Any other input throws an `\UnexpectedValueException`.

Possible outputs
----------------

`string`

Options
-------

| Code        | Type     | Required | Default | Description                              |
|-------------|----------|:--------:|---------|------------------------------------------|
| `separator` | `string` |          | `'\|'`  | String inserted between each element     |

Examples
--------

* `['1', '2', '3']` becomes `'1|2|3'`

```yaml
# Transformer options level
implode: ~
```

* Concatenate then slugify several fields

```yaml
# Transformer mapping level
slug:
  code:
    - '[id]'
    - '[firstname]'
    - '[lastname]'
  transformers:
    implode:
      separator: '-'
    slugify: ~
```
