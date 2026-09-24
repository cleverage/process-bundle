PregFilterTransformer
=====================

Perform a regular expression search and replace on the input using PHP
[`preg_filter()`](https://www.php.net/manual/en/function.preg-filter.php): unlike `preg_replace()`, `null` is returned
when the pattern does not match.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\PregFilterTransformer`
* **Transformer code**: `preg_filter`

Accepted inputs
---------------

Any value that can be cast to string.

Possible outputs
----------------

`string|null`: the replaced string, or `null` if no pattern matches (or on regex error).

Options
-------

| Code          | Type            | Required | Default | Description                                                                                           |
|---------------|-----------------|:--------:|---------|-------------------------------------------------------------------------------------------------------|
| `pattern`     | `string\|array` |  **X**   |         | Pattern, or list of patterns, to search                                                               |
| `replacement` | `string\|array` |  **X**   |         | Replacement string. The value is cast to string, so an array is not supported in practice (see Notes) |

Examples
--------

```yaml
# Transformer options level
preg_filter:
  pattern: '/[^a-z0-9]/'
  replacement: ''
```

* Reformat a date, `null` if the input does not match

```yaml
# Transformer options level
preg_filter:
  pattern: '/^(\d{2})\/(\d{2})\/(\d{4})$/'
  replacement: '$3-$2-$1'
```

Notes
-----

Although `replacement` accepts an `array`, it is cast to string before calling `preg_filter()`, which results in the
literal `Array` (and a PHP warning).
