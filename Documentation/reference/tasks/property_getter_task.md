PropertyGetterTask
==================

Accepts an array or an object as an input and read a value from a property path.

See [PropertyAccess Component Reference](https://symfony.com/doc/current/components/property_access.html) for details on property path syntax and behavior.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\PropertyGetterTask`

Accepted inputs
---------------

`array` or `object` that can be accessed by the property accessor

Possible outputs
----------------

Value of the property extracted from input.

Options
-------

| Command | Type | Required | Default | Description |
| ------- | ---- | :------: | ------- | ----------- |
| `property` | `string` | **X** | | Property path to read from input |

