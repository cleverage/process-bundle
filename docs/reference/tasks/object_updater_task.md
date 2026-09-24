ObjectUpdaterTask
=================

Takes an array containing an object and a value, sets the value on the object at the configured property path, then
outputs the updated object.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ObjectUpdaterTask`

Accepted inputs
---------------

`array` with two keys:

* `object`: the object (or array) to update
* `value`: the value to set

An `\UnexpectedValueException` is thrown if one of these keys is missing.

Possible outputs
----------------

The `object` of the input, updated with the `value`.

Options
-------

| Code            | Type     | Required | Default | Description                                                 |
|-----------------|----------|:--------:|---------|-------------------------------------------------------------|
| `property_path` | `string` |  **X**   |         | Property path of the `object` where the `value` will be set |

Examples
--------

* Update the `name` of an object

```yaml
# Task configuration level
update_object:
  service: '@CleverAge\ProcessBundle\Task\ObjectUpdaterTask'
  options:
    property_path: name
  outputs: [save]
```

With the input `['object' => $myEntity, 'value' => 'New Name']`, the property accessor sets `name` to `New Name` on
`$myEntity` (e.g. through `setName()`), and `$myEntity` is output.
