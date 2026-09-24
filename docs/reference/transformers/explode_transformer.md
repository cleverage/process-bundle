ExplodeTransformer
==================

Split a string into an array, using PHP's [explode](https://www.php.net/manual/en/function.explode.php) function.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\ExplodeTransformer`
* **Transformer code**: `explode`

Accepted inputs
---------------

Any value that can be cast to `string`, or `null`.

Possible outputs
----------------

`array`: the list of parts; an empty array if the input is `null` or an empty string

Options
-------

| Code        | Type     | Required | Default | Description                                 |
|-------------|----------|:--------:|---------|---------------------------------------------|
| `delimiter` | `string` |  **X**   |         | The boundary string, must not be empty      |

Examples
--------

* `'a,b,c'` becomes `['a', 'b', 'c']`

```yaml
# Transformer options level
explode:
  delimiter: ','
```

* Split then trim each part: `'a, b ,c'` becomes `['a', 'b', 'c']`

```yaml
# Transformer mapping level
tags:
  code: '[tags]'
  transformers:
    explode:
      delimiter: ','
    array_map:
      transformers:
        trim: ~
```
