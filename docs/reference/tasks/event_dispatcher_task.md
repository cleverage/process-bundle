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
clever_age_process:
  configurations:
    project_prefix.event_dispatcher_example:
      tasks:
        event_dispatcher_example:
          service: '@CleverAge\ProcessBundle\Task\Event\EventDispatcherTask'
          options:
            event_name: 'myapp.myevent'
          outputs: [debug]
        debug:
          service: '@CleverAge\ProcessBundle\Task\Debug\DebugTask'
```