ProcessExecutorTask
===================

Execute another process as a subprocess, passing the current input as the subprocess input. The subprocess output
is set as the task output. This allows chaining and composing processes.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask`

Accepted inputs
---------------

`any`: passed as input to the subprocess entry point

Possible outputs
----------------

`any`: the output of the subprocess end point

Options
-------

| Code      | Type     | Required | Default | Description                                                           |
|-----------|----------|:--------:|---------|-----------------------------------------------------------------------|
| `process` | `string` | **X**    |         | Code of the process to execute (must exist in the process registry)   |
| `context` | `array`  |          | `[]`    | Context variables passed to the subprocess                            |

Example
-------

```yaml
# Task configuration level
run_subprocess:
  service: '@CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask'
  options:
    process: my.sub_process
    context:
      environment: production
```
