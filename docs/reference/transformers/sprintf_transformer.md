SprintfTransformer
==================

Return a formatted string, using PHP's [vsprintf](https://www.php.net/manual/en/function.vsprintf.php) function.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\SprintfTransformer`
* **Transformer code**: `sprintf`

Accepted inputs
---------------

* `array`: each element is used as an argument of the format, in order
* any other value (scalar, `null`, `\Stringable`): used as the single argument of the format

Possible outputs
----------------

`string`

Options
-------

| Code     | Type     | Required | Default | Description                                                                                          |
|----------|----------|:--------:|---------|------------------------------------------------------------------------------------------------------|
| `format` | `string` |          | `'%s'`  | The [format string](https://www.php.net/manual/en/function.sprintf.php); see [Notes](#notes) for `%` |

Examples
--------

* `'bar'` becomes `'foo bar'`

```yaml
# Transformer options level
sprintf:
  format: 'foo %%s'
```

* Format one value

```yaml
# Transformer mapping level
sprintf_one:
  code: '[id]'
  transformers:
    sprintf:
      format: 'one/%%d'
```

* Format several values

```yaml
# Transformer mapping level
sprintf_multiple:
  code:
    - '[firstname]'
    - '[lastname]'
  transformers:
    sprintf:
      format: 'multiple/%%s/%%s'
```

Notes
-----

In Symfony YAML configuration files, `%` is used for container parameters: escape it as `%%` (`'%%s'` is resolved as
`'%s'`). A format with more placeholders than arguments throws a `\ValueError`.
