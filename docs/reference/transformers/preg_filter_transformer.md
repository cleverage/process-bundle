PregFilterTransformer
=====================

Apply PHP's `preg_filter()` on the input value. Returns the value after performing a regex search and replace,
or `null` if the pattern does not match.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\PregFilterTransformer`
* **Transformer code**: `preg_filter`

Accepted inputs
---------------

`string`: the value to filter

Possible outputs
----------------

`string|null`: the filtered value, or `null` if the pattern does not match

Options
-------

| Code          | Type             | Required | Default | Description                    |
|---------------|------------------|:--------:|---------|--------------------------------|
| `pattern`     | `string\|array`  | **X**    |         | Regex pattern(s) to search     |
| `replacement` | `string\|array`  | **X**    |         | Replacement string(s)          |

Examples
--------

```yaml
# Transformer options level
preg_filter:
  pattern: '/[^a-z0-9]/'
  replacement: ''
```
