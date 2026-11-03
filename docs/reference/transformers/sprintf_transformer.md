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
`'%s'`). An invalid format (e.g. a `%` alone, not escaped as `%%`) is rejected with the configuration. A format with
more placeholders than arguments throws an `\ArgumentCountError`, with the value.

The `%f` conversion depends on the locale: once the application has called `setlocale()` (e.g. with `fr_FR.UTF-8`),
`%.2f` formats `1.5` as `1,50`. Use `%F`, which always uses a dot (`1.50`), for a number written to a file or sent to an
API. PHP does not inherit the locale from the environment (`LANG`, `LC_ALL`): without a call to `setlocale()`, both
give `1.50`.
