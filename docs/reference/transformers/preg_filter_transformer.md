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
| `replacement` | `string\|array` |  **X**   |         | Replacement string, or list of replacements (only when `pattern` is an array)                         |

Examples
--------

```yaml
# Transformer options level
preg_filter:
  pattern: '/[^a-z0-9]/'
  replacement: ''
```

* Replace several patterns at once: each pattern is replaced by the replacement at the same position

```yaml
# Transformer options level
preg_filter:
  pattern: ['/a/', '/b/']
  replacement: ['1', '2']
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

An array `replacement` requires an array `pattern` (as in `preg_filter()`): otherwise an `InvalidOptionsException` is
thrown when resolving the options. If `replacement` has fewer elements than `pattern`, the missing replacements are
empty strings.
