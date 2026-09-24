EventDispatcherTask
===================

Dispatches a `CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent` through the Symfony event dispatcher. The event
gives listeners access to the current `ProcessState` (`getState()`), so they can read the input and, when the task is
not passive, set the output themselves.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Event\EventDispatcherTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

* `any`: the input, unchanged, when `passive` is `true`
* when `passive` is `false`: whatever a listener set with `$event->getState()->setOutput()`, `null` otherwise

Options
-------

| Code         | Type     | Required | Default | Description                                                   |
|--------------|----------|:--------:|---------|---------------------------------------------------------------|
| `event_name` | `string` |  **X**   |         | Name of the event (see Notes: currently not used to dispatch) |
| `passive`    | `bool`   |          | `true`  | If `true`, the input is passed to the output before dispatch  |

Examples
--------

* Dispatch an event for each item

```yaml
# Task configuration level
data:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  options:
    output: [1, 2, 3]
  outputs: [push_data_event]
push_data_event:
  service: '@CleverAge\ProcessBundle\Task\Event\EventDispatcherTask'
  options:
    event_name: myapp.data_queue
```

Notes
-----

The event is dispatched without an explicit name (`$eventDispatcher->dispatch($event)`), so its name is the event
class name. Listeners must therefore subscribe to `CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent`; the
`event_name` option is required and validated but not used for dispatching.
