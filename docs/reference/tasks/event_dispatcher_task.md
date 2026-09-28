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

| Code         | Type           | Required | Default | Description                                                                  |
|--------------|----------------|:--------:|---------|------------------------------------------------------------------------------|
| `event_name` | `string\|null` |          | `null`  | Name of the dispatched event, `null` to use the event class name (see Notes) |
| `passive`    | `bool`         |          | `true`  | If `true`, the input is passed to the output before dispatch                 |

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

The event is dispatched with `$eventDispatcher->dispatch($event, $eventName)`:

* when `event_name` is set, listeners must subscribe to that name:

```php
#[AsEventListener(event: 'myapp.data_queue')]
public function onDataQueue(EventDispatcherTaskEvent $event): void
{
    $input = $event->getState()->getInput();
}
```

* when `event_name` is `null`, the event name is the event class name, so listeners must subscribe to
  `CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent`. Every `EventDispatcherTask` without `event_name` then
  triggers the same listeners.

**Deprecated since v5, removed in v6.0**: from v4.0 to v5.0, the `event_name` option was ignored and the event was
always dispatched under its class name. To preserve backward compatibility, when `event_name` is set (and differs from the
event class name) and listeners subscribe to `CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent`, the event is also
dispatched under its class name and an `E_USER_DEPRECATED` error is triggered. In v6.0, listen to the configured
`event_name` instead, or remove the `event_name` option.
