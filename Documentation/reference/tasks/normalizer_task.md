NormalizerTask
==============

Normalize data from the input and pass it to the output

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Serialization\NormalizerTask`

Accepted inputs
---------------

Any normalizable object.

Possible outputs
----------------

A normalized value as an array.

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `format` | `string` | **X** | | Format for normalization ("json", "xml", ... an empty string should also work) |
| `context` | `array` | | `[]` | Will be passed directly to the third parameter of the normalize method |

