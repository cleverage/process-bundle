PregMatchTransformer
=========================

Perform a regular expression match

This transformer uses the php internal function: https://www.php.net/manual/en/function.preg-match.php

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\PregMatchTransformer`
* **Transformer code**: `preg_match`

Accepted inputs
---------------

`string`

Possible outputs
----------------

`array` or `null`

Options
-------

| Code       | Type      | Required | Default | Description                              |
|------------|-----------|:--------:|---------|------------------------------------------|
| `pattern`  | `string`  |  **X**   |         |                                          |
| `flags`    | `int`     |          | 0       |                                          |
| `offset`   | `int`     |          | 0       |                                          |
| `mode_all` | `boolean` |          | false   | Use preg_match_all instead of preg_match |

Examples
--------

```yaml
# Transformer options level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  outputs: [ preg_match ]
  options:
    output: 'foobarbaz'
preg_match:
  service: '@CleverAge\ProcessBundle\Task\TransformerTask'
  options:
    transformers:
      preg_match:
        pattern: '/(foo)(bar)(baz)/'
        flags: !php/const PREG_OFFSET_CAPTURE
      property_accessor:
        property_path: '[2]'
```
