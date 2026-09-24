DateParserTransformer
=====================

Parse a string into a `\DateTime`, using `\DateTime::createFromFormat()`.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Date\DateParserTransformer`
* **Transformer code**: `date_parser`

Accepted inputs
---------------

* `string`: a date matching the configured `format`
* `\DateTime`, returned unchanged
* Any falsy value (`null`, `''`, `false`…), returned unchanged

A string that cannot be parsed with the given format throws an `\UnexpectedValueException`.

Possible outputs
----------------

* `\DateTime`
* the input value itself if it is falsy

Options
-------

| Code     | Type     | Required | Default | Description                                                                                                        |
|----------|----------|:--------:|---------|--------------------------------------------------------------------------------------------------------------------|
| `format` | `string` |  **X**   |         | Input format, see [DateTime::createFromFormat](https://www.php.net/manual/en/datetime.createfromformat.php)        |

Examples
--------

* Read the string `2019-12-02`

```yaml
# Transformer options level
date_parser:
  format: Y-m-d
```

* Read the string `02/12/2019 14:30`

```yaml
# Transformer options level
date_parser:
  format: 'd/m/Y H:i'
```

Notes
-----

Fields missing from `format` are taken from the current time (e.g. with `Y-m-d`, the time part is the current time). Use
the `!` or `|` format characters to reset them, e.g. `'!Y-m-d'`.

A `\DateTimeImmutable` input is not returned unchanged: it is passed to `createFromFormat()` and throws a `\TypeError`.
