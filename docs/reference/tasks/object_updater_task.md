ObjectUpdaterTask
=================

Takes an array containing an object and a value, updates the object's property with the given value, then returns
the updated object.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ObjectUpdaterTask`

Accepted inputs
---------------

`array`: must contain two keys:
* `object`: the object to update
* `value`: the value to set on the property

Possible outputs
----------------

`object`: the updated object

Options
-------

| Code            | Type     | Required | Default | Description                                               |
|-----------------|----------|:--------:|---------|-----------------------------------------------------------|
| `property_path` | `string` | **X**    |         | Property path on the object where the value will be set   |

Example
-------

```yaml
# Task configuration level
update_object:
  service: '@CleverAge\ProcessBundle\Task\ObjectUpdaterTask'
  options:
    property_path: 'name'
```

With the following input:
```php
['object' => $myEntity, 'value' => 'New Name']
```
The task will call `$myEntity->setName('New Name')` and output `$myEntity`.
