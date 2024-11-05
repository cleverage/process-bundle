CounterTask
==================

Count the number of times the task is processed and continue every N iteration (skip the rest of the time)

Flush at the end with the actual count.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\CounterTask`

Accepted inputs
---------------

`any`

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
clever_age_process:
  configurations:
    project_prefix.counter_example:
      tasks:
        counter_example:
          service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
          options:
            output:
              test1: test1
              test2: test2
              test3: test3
              test4: test4
              test5: test5
              test6: test6
          outputs: [counter]
        counter:
          service: '@CleverAge\ProcessBundle\Task\CounterTask'
          options:
            flush_every: 2
          outputs: [ debug ]
        debug:
          service: '@CleverAge\ProcessBundle\Task\Debug\DebugTask'
```