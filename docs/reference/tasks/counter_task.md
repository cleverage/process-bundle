CounterTask
===========

Counts the number of times the task is executed and only outputs the current count every `flush_every` executions
(the task is skipped the rest of the time). When flushed (at the end of the upstream iteration), it outputs the
final count, unless this count is a multiple of `flush_every` (in which case it has already been sent).

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\CounterTask`
* **Flushable task**

Accepted inputs
---------------

Input is ignored (only the number of executions matters)

Possible outputs
----------------

`int`: the number of times the task has been executed so far

Options
-------

| Code          | Type  | Required | Default | Description                                           |
|---------------|-------|:--------:|---------|-------------------------------------------------------|
| `flush_every` | `int` |  **X**   |         | Output the count every N executions (N = this option) |

Examples
--------

* Count 9 iterated items, outputting `3`, `6` and `9`; with `flush_every: 4` it would output `4`, `8`, then `9` on
  flush

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  options:
    output: [1, 2, 3, 4, 5, 6, 7, 8, 9]
  outputs: [counter]
counter:
  service: '@CleverAge\ProcessBundle\Task\CounterTask'
  options:
    flush_every: 3
  outputs: [debug]
```

Notes
-----

* `flush()` can be called several times during a process (see
  [Advanced workflow](../../04-advanced_workflow.md)), e.g. once at the end of the upstream iteration and once when
  the counter itself is resolved. Each call outputs the current count again (unless it is a multiple of `flush_every`),
  so the final count may be sent more than once to the next tasks.
