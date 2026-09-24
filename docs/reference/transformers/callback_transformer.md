CallbackTransformer
===================

Call a PHP callable (function or static method) with the input value. The value is inserted between configurable left
and right parameters: `callback(...left_parameters, $value, ...right_parameters)`.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\CallbackTransformer`
* **Transformer code**: `callback`

Accepted inputs
---------------

`any`: whatever the callback accepts.

Possible outputs
----------------

`any`: the return value of the callback.

Options
-------

| Code                    | Type            | Required | Default | Description                                                                                               |
|-------------------------|-----------------|:--------:|---------|-----------------------------------------------------------------------------------------------------------|
| `callback`              | `string\|array` |  **X**   |         | A valid PHP callable (e.g. `strtoupper`, `['App\MyClass', 'myStaticMethod']`), checked with `is_callable` |
| `left_parameters`       | `array`         |          | `[]`    | Parameters passed before the value                                                                        |
| `right_parameters`      | `array`         |          | `[]`    | Parameters passed after the value                                                                         |
| `additional_parameters` | `array`         |          | `[]`    | **Deprecated**: use `right_parameters` instead. Only used when `right_parameters` is empty                |

Examples
--------

* Simple PHP function

```yaml
# Transformer options level
callback:
  callback: strtoupper
```

* Function with extra parameters: `json_decode($value, true)`

```yaml
# Transformer options level
callback:
  callback: json_decode
  right_parameters: [true]
```

* Value in second position: `explode(',', $value)`

```yaml
# Transformer options level
callback:
  callback: explode
  left_parameters: [',']
```
