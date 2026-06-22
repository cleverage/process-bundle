ProcessLauncherTask
===================

Launch parallel subprocesses for each input received. Each subprocess is executed as a separate system process
(CLI), allowing true parallelization. The task manages a pool of running processes, waits for them to complete,
and outputs their results.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask`
* **Iterable task**
* **Flushable task**

Accepted inputs
---------------

`scalar|resource|\Traversable`: input passed as stdin to the subprocess command

Possible outputs
----------------

`any`: the result of each finished subprocess (if available)

Options
-------

| Code                           | Type             | Required | Default | Description                                                                              |
|--------------------------------|------------------|:--------:|---------|------------------------------------------------------------------------------------------|
| `process`                      | `string`         | **X**    |         | Code of the process to launch (must exist in the process registry)                       |
| `max_processes`                | `integer`        |          | `3`     | Maximum number of concurrent subprocesses                                                |
| `sleep_interval`               | `integer\|float` |          | `1`     | Time in seconds to wait when the pool is full                                            |
| `sleep_interval_after_launch`  | `integer\|float` |          | `1`     | Time in seconds to wait after launching a new subprocess                                 |
| `sleep_on_finalize_interval`   | `integer\|float` |          | `1`     | Time in seconds to wait while checking for remaining processes during finalization       |
| `context`                      | `array`          |          | `[]`    | Context variables passed to subprocesses                                                 |
| `json_buffering`               | `boolean`        |          | `false` | Enable JSON buffering for subprocess output                                              |

Example
-------

```yaml
# Task configuration level
parallel_launch:
  service: '@CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask'
  options:
    process: my.worker_process
    max_processes: 5
    sleep_interval: 0.5
    context:
      mode: parallel
```
