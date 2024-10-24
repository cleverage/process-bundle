DateParserTransformer
=====================

Read a `string` to deduce the matching `\DateTime`. It will throw an error if the date cannot be read.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\DateParserTransformer`
* **Transformer code**: `date_parser`

Accepted inputs
---------------

`string`

Possible outputs
----------------

`\DateTime`

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `format` | `string` | **X** | | See [PHP date formats](https://www.php.net/manual/fr/function.date.php) for supported values |

Examples
--------

* Example : this will correctly read the string "2019-12-02"
  
```yaml
# Transformer options level
transformers:
    date_parser:
        format: Y-m-d
```
