DateFormatTransformer
=====================

Format a date object into a `string`, using `\DateTimeInterface::format()`.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Date\DateFormatTransformer`
* **Transformer code**: `date_format`

Accepted inputs
---------------

* `\DateTimeInterface` (`\DateTime` or `\DateTimeImmutable`)
* Any falsy value (`null`, `''`, `false`…), returned unchanged

Any other value (including a date string) throws an `\UnexpectedValueException`. Use
[DateParserTransformer](date_parser_transformer.md) first to convert a string into a date.

Possible outputs
----------------

* `string`: the formatted date
* the input value itself if it is falsy

Options
-------

| Code     | Type     | Required | Default | Description                                                                                          |
|----------|----------|:--------:|---------|------------------------------------------------------------------------------------------------------|
| `format` | `string` |  **X**   |         | Output format, see [PHP date formats](https://www.php.net/manual/en/datetime.format.php)             |

Examples
--------

* Output a string like `2019-12-02`

```yaml
# Transformer options level
date_format:
  format: Y-m-d
```

* Parse a French date and convert it to ISO 8601

```yaml
# Transformer mapping level
created_at:
  code: '[date]'
  transformers:
    date_parser:
      format: d/m/Y
    date_format:
      format: 'Y-m-d\TH:i:sP'
```
