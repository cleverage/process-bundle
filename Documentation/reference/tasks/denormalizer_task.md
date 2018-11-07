DenormalizerTask
================

Denormalize data from the input and pass it to the output

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask`

Accepted inputs
---------------

`array`

Possible outputs
----------------

`object`, instance of `class`, as a product of the denormalization

Options
-------

| Command | Type | Required | Default | Description |
| ------- | ---- | :------: | ------- | ----------- |
| `class` | `string` | **X** | | Destination class for denormalization |
| `format` | `string` | | `null` | Format for denormalization ("json", "xml", ... an empty string should also work) |
| `context` | `array` | | `[]` | Will be passed directly to the 4th parameter of the denormalize method |

