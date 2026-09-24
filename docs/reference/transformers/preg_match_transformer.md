PregMatchTransformer
====================

Perform a regular expression match, using PHP's [preg_match](https://www.php.net/manual/en/function.preg-match.php)
or [preg_match_all](https://www.php.net/manual/en/function.preg-match-all.php) function, and return the matches.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\PregMatchTransformer`
* **Transformer code**: `preg_match`

Accepted inputs
---------------

Any value that can be cast to `string`, or `null`.

Possible outputs
----------------

* `array`: the `$matches` array filled by `preg_match` / `preg_match_all` (an empty array when nothing matches with
  `preg_match`)
* `null` if the input is `null` or an empty string

Options
-------

| Code       | Type     | Required | Default | Description                                                                                          |
|------------|----------|:--------:|---------|------------------------------------------------------------------------------------------------------|
| `pattern`  | `string` |  **X**   |         | The regular expression, with delimiters                                                              |
| `flags`    | `int`    |          | `0`     | `PREG_*` flags passed to the function (e.g. `!php/const PREG_OFFSET_CAPTURE`)                        |
| `offset`   | `int`    |          | `0`     | Offset (in bytes) from which to start the search                                                     |
| `mode_all` | `bool`   |          | `false` | If `true`, use `preg_match_all` instead of `preg_match`                                              |

Examples
--------

* `'foobarbaz'` becomes `['foobarbaz', 'foo', 'bar', 'baz']`

```yaml
# Transformer options level
preg_match:
  pattern: '/(foo)(bar)(baz)/'
```

* Capture offsets and keep only the 2nd group: `'foobarbaz'` becomes `['bar', 3]`

```yaml
# Transformer mapping level
second_group:
  code: '[text]'
  transformers:
    preg_match:
      pattern: '/(foo)(bar)(baz)/'
      flags: !php/const PREG_OFFSET_CAPTURE
    property_accessor:
      property_path: '[2]'
```

* Get all numbers of a string: `'a1b22c333'` becomes `[['1', '22', '333']]`

```yaml
# Transformer options level
preg_match:
  pattern: '/\d+/'
  mode_all: true
```
