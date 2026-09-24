RecursivePropertySetterTransformer
==================================

Read an iterable from the input, then set one or more properties on each of its items, using values read from the
input itself. Typically used to propagate a parent value (an id, a code…) to each child of a collection.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Object\RecursivePropertySetterTransformer`
* **Transformer code**: `recursive_property_setter`

Accepted inputs
---------------

`array` or `object` readable by the Symfony PropertyAccessor, containing an iterable at the `iterator` path. `null` is
accepted only when `ignore_null` is `true`.

Possible outputs
----------------

* `iterable`: the collection read at the `iterator` path, with the properties set on each item (the rest of the input
  is not returned)
* `null` if the input is `null` and `ignore_null` is `true`, or if the `iterator` path is not readable and
  `ignore_missing` is `true`

Options
-------

| Code             | Type     | Required | Default | Description                                                                                                   |
|------------------|----------|:--------:|---------|---------------------------------------------------------------------------------------------------------------|
| `iterator`       | `string` |  **X**   |         | Property path of the collection in the input; a non-iterable value throws a `TransformerException`            |
| `set_properties` | `array`  |  **X**   |         | Map of `item_property_path: input_property_path`; each value is read from the input and written on each item  |
| `ignore_null`    | `bool`   |          | `false` | If `true`, a `null` input returns `null` and `null` source values are allowed (else a `TransformerException`) |
| `ignore_missing` | `bool`   |          | `false` | If `true`, an unreadable `iterator` path returns `null` and an unreadable source value is set as `null`       |

Examples
--------

* Propagate the parent id and name to each item

```yaml
# Transformer options level
recursive_property_setter:
  iterator: '[items]'
  set_properties:
    '[parentId]': '[id]'
    '[parentName]': '[name]'
```

With input `{id: 1, name: 'Parent', items: [{label: 'A'}, {label: 'B'}]}`, the output is
`[{label: 'A', parentId: 1, parentName: 'Parent'}, {label: 'B', parentId: 1, parentName: 'Parent'}]`.

Notes
-----

Keys of `set_properties` are property paths written with the PropertyAccessor on each item: use the `[key]` notation
for array items and the `property` notation for objects. For `\stdClass` items, a property that cannot be written is
added to the object.

Object items are modified in place, so the objects of the input are modified too.
