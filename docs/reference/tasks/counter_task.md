CounterTask
==================

Count the number of times the task is processed and continue every N iteration (skip the rest of the time)

Flush at the end with the actual count.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\CounterTask`

Accepted inputs
---------------

`any`, must implement IterableTaskInterface

Possible outputs
----------------

`int`: outputs the number of times the counter is called

Options
-------

| Code          | Type  | Required | Default  | Description                                       |
|---------------|-------|----------|----------|---------------------------------------------------|
| `flush_every` | `int` | **X**    |          | The period at which the task will produce outputs |

Example
-------

```yaml
# Task configuration level
code:
  service: '@CleverAge\ProcessBundle\Task\CounterTask'
  options:
    flush_every: 2
```
