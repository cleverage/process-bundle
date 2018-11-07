PropertySetterTask
==================

Accepts an array or an object as an input and sets values before returning it as the output.

See [PropertyAccess Component Reference](https://symfony.com/doc/current/components/property_access.html) for details on property path syntax and behavior.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\PropertySetterTask`

Accepted inputs
---------------

`array` or `object` that can be accessed by the property accessor

Possible outputs
----------------

Same `array` or `object`, with the property changed

Options
-------

| Command | Type | Required | Default | Description |
| ------- | ---- | :------: | ------- | ----------- |
| `values` | `array` | **X** | | List of property path => value to set in the input |

