RecursivePropertySetterTransformer
===================================

Read an iterable collection from the input, then set one or more properties on each item of that collection using
values read from the input itself.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Object\RecursivePropertySetterTransformer`
* **Transformer code**: `recursive_property_setter`

Accepted inputs
---------------

`array` or `object`: must contain an iterable property (specified by `iterator`) and the source properties
(specified in `set_properties`)

Possible outputs
----------------

`iterable`: the modified collection with properties set on each item

Options
-------

| Code              | Type      | Required | Default | Description                                                                                   |
|-------------------|-----------|:--------:|---------|-----------------------------------------------------------------------------------------------|
| `iterator`        | `string`  | **X**    |         | Property path to the iterable collection within the input                                     |
| `set_properties`  | `array`   | **X**    |         | Map of `property_name => property_path`, where path is read from the input                    |
| `ignore_null`     | `boolean` |          | `false` | If `true`, allow `null` values for source properties                                          |
| `ignore_missing`  | `boolean` |          | `false` | If `true`, skip unreadable source properties instead of throwing                              |

Examples
--------

* Set a parent reference on each child item

```yaml
# Transformer options level
recursive_property_setter:
  iterator: 'items'
  set_properties:
    parentId: 'id'
    parentName: 'name'
```

With input `{id: 1, name: "Parent", items: [{label: "A"}, {label: "B"}]}`, each item in `items` will receive
`parentId: 1` and `parentName: "Parent"`.
