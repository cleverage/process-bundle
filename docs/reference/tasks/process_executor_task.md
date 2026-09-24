ProcessExecutorTask
===================

Executes another process synchronously (in the same PHP process) for each input, passing the input to the
sub-process entry point. The output of the sub-process end point becomes the task output. This allows composing
processes.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask`

Accepted inputs
---------------

`mixed`: passed as input to the sub-process (its `entry_point` task).

Possible outputs
----------------

`mixed`: output of the sub-process `end_point` task, or `null` if the sub-process has no end point.

Options
-------

| Code      | Type     | Required | Default | Description                                                                                       |
|-----------|----------|:--------:|---------|---------------------------------------------------------------------------------------------------|
| `process` | `string` |  **X**   |         | Code of the process to execute. An `InvalidConfigurationException` is thrown if it does not exist |
| `context` | `array`  |          | `[]`    | Context of the sub-process (the context of the current process is **not** passed automatically)   |

Examples
--------

* Execute a sub-process for each input, forwarding a context value

```yaml
# Task configuration level
run_subprocess:
  service: '@CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask'
  options:
    process: app.import_product
    context:
      source: '{{ source }}'
  outputs: [next_task]
```
