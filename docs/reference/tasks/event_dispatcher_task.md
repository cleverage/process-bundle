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

| Code         | Type     | Required  | Default  | Description          |
|--------------|----------|:---------:|----------|----------------------|
| `event_name` | `string` |   **X**   |          |                      |
| `passive`    | `bool`   |           | `true`   | Pass input to output |

Example
-------

```yaml
# Task configuration level
code:
  service: '@CleverAge\ProcessBundle\Task\Event\EventDispatcherTask'
  options:
    event_name: 'myapp.myevent'
```
