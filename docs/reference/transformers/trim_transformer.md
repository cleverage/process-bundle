TrimTransformer
===============

Strip whitespace (or other characters) from the beginning and end of a string, using PHP's
[trim](https://www.php.net/manual/en/function.trim.php) function.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\TrimTransformer`
* **Transformer code**: `trim`

Accepted inputs
---------------

Any value that can be cast to `string`, or `null`.

Possible outputs
----------------

* `string`: the trimmed value
* `null` if the input is `null`

Options
-------

| Code       | Type     | Required | Default             | Description                                                    |
|------------|----------|:--------:|---------------------|----------------------------------------------------------------|
| `charlist` | `string` |          | `" \t\n\r\0\x0B"`   | Characters to strip (ranges like `a..z` are supported)         |

Examples
--------

* `'  trim me  '` becomes `'trim me'`

```yaml
# Transformer options level
trim: ~
```

* `'-trim me-'` becomes `'trim me'`

```yaml
# Transformer options level
trim:
  charlist: '-'
```
