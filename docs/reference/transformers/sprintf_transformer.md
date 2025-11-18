SprintfTransformer
=========================

Return a formatted string.

This transformer uses the php internal function: https://www.php.net/manual/en/function.vsprintf.php

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\SprintfTransformer`
* **Transformer code**: `sprintf`

Accepted inputs
---------------

Any value that can be cast to `string` | `int` | `float` or `array`

Possible outputs
----------------

`string`

Options
-------

| Code     | Type     | Required | Default | Description                                                                                                          |
|----------|----------|:--------:|---------|----------------------------------------------------------------------------------------------------------------------|
| `format` | `string` |  **X**   | `%s`    | The format string is composed of zero or more directives. Escape % with another %% due to ParameterBag restrictions. |

Examples
--------

```yaml
# Transformer mapping level
sprintf_one:
  code: '[firstname]'
  transformers:
    sprintf:
      format: 'one/%%d'
sprintf_multiple:
  code:
    - '[firstname]'
    - '[lastname]'
  transformers:
    sprintf:
      format: 'multiple/%%s/%%s'
```
