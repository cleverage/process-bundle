PropertyAccessorTransformer
===========================

Read a value from the input using the Symfony [PropertyAccessor](https://symfony.com/doc/current/components/property_access.html)
and return it.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Object\PropertyAccessorTransformer`
* **Transformer code**: `property_accessor`

Accepted inputs
---------------

`array` or `object` readable by the PropertyAccessor. `null` is accepted only when `ignore_null` is `true`.

Possible outputs
----------------

* `any`: the value found at the property path
* `null` if the input is `null` and `ignore_null` is `true`, or if the path is not readable and `ignore_missing` is
  `true`

Options
-------

| Code             | Type     | Required | Default | Description                                                                              |
|------------------|----------|:--------:|---------|------------------------------------------------------------------------------------------|
| `property_path`  | `string` |  **X**   |         | Property path to read (e.g. `[key]` for arrays, `property` for objects, `[a][b]`, `a.b`) |
| `ignore_null`    | `bool`   |          | `false` | If `true`, return `null` when the input is `null` instead of failing                     |
| `ignore_missing` | `bool`   |          | `false` | If `true`, return `null` when the property path is not readable instead of failing       |

Examples
--------

* Read a nested property of an object

```yaml
# Transformer options level
property_accessor:
  property_path: 'FullCountryInfoAllCountriesResult.tCountryInfo'
```

* Read the 3rd element of a `preg_match` result

```yaml
# Transformer mapping level
third_element:
  code: '[text]'
  transformers:
    preg_match:
      pattern: '/(foo)(bar)(baz)/'
    property_accessor:
      property_path: '[2]'
```

* Read an optional nested array key

```yaml
# Transformer options level
property_accessor:
  property_path: '[address][city]'
  ignore_missing: true
```
