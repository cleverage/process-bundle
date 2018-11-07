EventDispatcherTask
===================

Call the Symfony event dispatcher

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Event\EventDispatcherTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

* `any` when `passive` option is set to true
* `null` in other cases

Options
-------

| Command | Type | Required | Default | Description |
| ------- | ---- | :------: | ------- | ----------- |
| `event_name` | `string` | **X** | | Format for normalization ("json", "xml", ... an empty string should also work) |
| `passive` | `bool` | | `true` | Pass input to output |

