ProcessExecutorTask
===================

Executes another process synchronously (in the same PHP process) for each input, passing the input to the
sub-process entry point. The output of the sub-process end point becomes the task output. This allows composing
processes.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Process\ProcessExecutorTask`
* **Code**: `process_executor` (`task: process_executor`)

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
  task: process_executor
  options:
    process: app.import_product
    context:
      source: '{{ source }}'
  outputs: [next_task]
```

Notes
-----

* When the sub-process fails, the `ProcessFailedException` is thrown by this task: its `error_strategy` applies. With
  `stop` (default), the parent process fails too; with `skip`, the parent process goes on with the next input (the
  input is sent to the `error_outputs` of the task, if any).
* The `process` option is checked when the task is initialized: with an unknown process, the initialization fails, the
  error is logged and, with process-bundle v5, the parent process fails when the task is first executed (see
  [initializable tasks](../../02-task_types.md#initializable-tasks)).
* Executing a process while it is already running, by itself or through its sub-processes (`A → B → A`), is
  deprecated since 5.6 and will throw an exception in 6.0: a process has only one set of task instances and states,
  which the second execution resets while the first one is running (the running process then returns the output of the
  deepest execution). A deprecation naming the chain of processes (`A -> B -> A`) is triggered, and the execution goes
  on. Iterate with an iterable task instead of recursing, or use the [ProcessLauncherTask](process_launcher_task.md),
  which runs the process in a separate PHP process. Executing the same sub-process several times in a row (e.g. for
  each input) is not concerned.
