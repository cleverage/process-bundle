MappingTransformer
==================

Build a (possibly) new array or object from the properties of the input.

The algorithm is:

* determine the destination (`initial_value`, or the input itself with `keep_input`)
* for each target property of `mapping`:
  - get the source value (from `constant`, `set_null`, or the `code` property path(s))
  - apply the property `transformers` on this value
  - write the result into the destination (with `merge_callback`, the property accessor, or as a simple array key)

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\MappingTransformer`
* **Transformer code**: `mapping`

Accepted inputs
---------------

`array` or `object` readable by the Symfony [PropertyAccessor](https://symfony.com/doc/current/components/property_access.html).

Possible outputs
----------------

`array` or `object`: the destination, filled with the mapped properties.

Options
-------

| Code             | Type             | Required | Default | Description                                                                                                                                                       |
|------------------|------------------|:--------:|---------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `mapping`        | `array`          |  **X**   |         | List of `target property => property options` (see below). The target is a writable property path of the destination, or a plain array key                        |
| `ignore_missing` | `bool`           |          | `false` | Ignore property accessor read errors for the whole mapping (the property is then skipped)                                                                         |
| `keep_input`     | `bool`           |          | `false` | Use the input as the destination. Cannot be combined with a non-empty `initial_value`. Due to PHP behavior, arrays are copied while objects are modified in place |
| `initial_value`  | `any`            |          | `[]`    | The destination to fill                                                                                                                                           |
| `merge_callback` | `callable\|null` |          | `null`  | Custom callable used to write each value, called with `($destination, $targetProperty, $value)`                                                                   |

Each property of `mapping` has the following options (`~` is allowed to use all defaults):

| Code             | Type                  | Required | Default | Description                                                                                                                                               |
|------------------|-----------------------|:--------:|---------|-----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `code`           | `string\|array\|null` |          | `null`  | Source property path, or list of `key => property path` to build an array. Defaults to the target property. The special value `.` returns the whole input |
| `constant`       | `any`                 |          | `null`  | If not `null`, used as the source value (takes precedence on `set_null` and `code`)                                                                       |
| `set_null`       | `bool`                |          | `false` | If `true`, `null` is used as the source value (takes precedence on `code`)                                                                                |
| `ignore_missing` | `bool`                |          | `false` | Ignore property accessor read errors for this property (with a list of paths, only the missing keys are skipped)                                          |
| `transformers`   | `array`               |          | `[]`    | Transformers applied on the source value, see [TransformerTrait](../traits/transformer_trait.md)                                                          |

Examples
--------

* Simple mapping
  - input: an array with keys `Code`, `label`, `Type`, `Name` and `ID`
  - output: an array with keys `code`, `label`, `type`, `reference`, `required` and `slug`

```yaml
# Transformer options level
mapping:
  mapping:
    code:                     # Simple mapping from "Code" to "code"
      code: '[Code]'
    '[label]': ~              # Value of "label" kept under the same key
    type:                     # Convert values, with a fallback
      code: '[Type]'
      transformers:
        convert_value:
          ignore_missing: true
          map:
            TEXTE: text
            NUMERIQUE: number
            DATE: date
        default:
          value: unknown
    reference:                # null value
      set_null: true
    required:                 # constant value
      constant: true
    slug:                     # Multiple sources, slugified and imploded
      code:
        name: '[Name]'
        id: '[ID]'
      transformers:
        array_map:
          transformers:
            slugify: ~
        implode:
          separator: '_'
```

* Nested mapping, using objects
  - input: an object with an iterable property `productItems`, containing objects with a property `longName`
  - output: an array with key `items`, containing a list of arrays with key `name`

```yaml
# Transformer options level
mapping:
  mapping:
    items:
      code: productItems
      transformers:
        array_map:
          transformers:
            mapping:
              mapping:
                name:
                  code: longName
```

* Update an object in place
  - input: an object with a property `address`, containing properties `postCode` and `customer` (itself having a
    property `hasFlag`)
  - output: the same object, with `address.customer.hasFlag` updated

```yaml
# Transformer options level
mapping:
  keep_input: true
  mapping:
    address.customer.hasFlag:
      code: address.postCode
      transformers:
        convert_value:
          ignore_missing: true
          map:
            69005: true
        default:
          value: false
```

Notes
-----

* Array values must be read with the index notation (`[key]`). By default, the Symfony PropertyAccessor returns `null`
  instead of throwing for a missing array index (`framework.property_access.throw_exception_on_invalid_index`), so
  `ignore_missing` mostly matters for objects.
* When a sub-transformer fails, the thrown `TransformerException` reports the target property.
* `merge_callback` receives the destination by value: to modify an array destination, the callable must take its
  first argument by reference.
