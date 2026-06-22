CallbackTransformer
===================

Apply a PHP callable (function or method) on the input value. The value is inserted between configurable left and
right parameters.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\CallbackTransformer`
* **Transformer code**: `callback`

Accepted inputs
---------------

`any`: passed as an argument to the callback

Possible outputs
----------------

`any`: return value of the callback

Options
-------

| Code                    | Type            | Required | Default | Description                                                                     |
|-------------------------|-----------------|:--------:|---------|---------------------------------------------------------------------------------|
| `callback`              | `string\|array` | **X**    |         | A valid PHP callable (e.g. `strtoupper`, `['MyClass', 'myMethod']`)             |
| `left_parameters`       | `array`         |          | `[]`    | Parameters prepended before the value in the callback call                      |
| `right_parameters`      | `array`         |          | `[]`    | Parameters appended after the value in the callback call                        |
| `additional_parameters` | `array`         |          | `[]`    | **Deprecated**: use `right_parameters` instead                                  |

The callback is called as: `callback(...left_parameters, $value, ...right_parameters)`

Examples
--------

* Using a simple PHP function

```yaml
# Transformer options level
callback:
  callback: strtoupper
```

* Using a function with parameters

```yaml
# Transformer options level
callback:
  callback: str_pad
  right_parameters: [10, '.', !php/const STR_PAD_LEFT]
```
