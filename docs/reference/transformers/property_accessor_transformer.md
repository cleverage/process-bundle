PropertyAccessorTransformer
===========================

Read a property from the input value using Symfony's PropertyAccessor and return it.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Object\PropertyAccessorTransformer`
* **Transformer code**: `property_accessor`

Accepted inputs
---------------

`array` or `object`: a value readable by Symfony's PropertyAccessor

Possible outputs
----------------

`any`: the value at the configured property path

Options
-------

| Code             | Type      | Required | Default | Description                                                         |
|------------------|-----------|:--------:|---------|---------------------------------------------------------------------|
| `property_path`  | `string`  | **X**    |         | Property path to read (e.g. `[key]`, `property`, `nested.path`)    |
| `ignore_null`    | `boolean` |          | `false` | If `true`, return `null` when the input value itself is `null`      |
| `ignore_missing` | `boolean` |          | `false` | If `true`, return `null` when the property path is not readable     |

Examples
--------

```yaml
# Transformer options level
property_accessor:
  property_path: '[address][city]'
  ignore_missing: true
```
