DateFormatTransformer
=====================

Transforms a `\DateTime` into a formatted `string`. It will throw an error if the date cannot be parsed.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\DateFormatTransformer`
* **Transformer code**: `date_format`

Accepted inputs
---------------

`\DateTime`

Possible outputs
----------------

`string`

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `format` | `string` | **X** | | See [PHP date formats](https://www.php.net/manual/fr/function.date.php) for supported values |

Examples
--------

* Example : this will output a string like "2019-12-02"
  
```yaml
# Transformer options level
transformers:
    date_format:
        format: Y-m-d
```
