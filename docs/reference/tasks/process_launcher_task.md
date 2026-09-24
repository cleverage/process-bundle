ProcessLauncherTask
===================

Launches a process in a separate system process (`bin/console cleverage:process:execute`) for each input received,
allowing parallelization. The task keeps a pool of at most `max_processes` running sub-processes and waits for a free
slot before launching a new one.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask`
* **Iterable task**
* **Flushable task**

Accepted inputs
---------------

`scalar|\Stringable|null`: the input is cast to string and passed to the sub-process through stdin
(`--input-from-stdin` option of the command).

Possible outputs
----------------

Nothing is output when an input is received (the task is skipped). When `json_buffering` is enabled, the sub-process
end point output (only if it is an array) is written to a JSON stream file (`var/cdm_buffer_*.json-stream`) and, once the sub-process is
finished, the task outputs the path of this file (`string`), during the next iterations or when flushed. Without
`json_buffering`, the task outputs nothing.

If a sub-process exits with a non-zero code, its error output is logged as critical, all running sub-processes are
stopped and a `\RuntimeException` is thrown.

Options
-------

| Code                          | Type         | Required | Default | Description                                                                                         |
|-------------------------------|--------------|:--------:|---------|-----------------------------------------------------------------------------------------------------|
| `process`                     | `string`     |  **X**   |         | Code of the process to launch. An `InvalidConfigurationException` is thrown if it does not exist    |
| `max_processes`               | `int`        |          | `3`     | Maximum number of sub-processes running at the same time                                            |
| `sleep_interval`              | `int\|float` |          | `1`     | Time (in seconds) to wait between two checks when the pool is full                                  |
| `sleep_interval_after_launch` | `int\|float` |          | `1`     | Time (in seconds) to wait after launching a sub-process                                             |
| `sleep_on_finalize_interval`  | `int\|float` |          | `1`     | Time (in seconds) to wait when the task has no finished result to output at the end of an iteration |
| `context`                     | `array`      |          | `[]`    | Context values passed to each sub-process (as `--context=key:value`, values must be scalars)        |
| `json_buffering`              | `bool`       |          | `false` | Store each sub-process output in a JSON stream file and output its path                             |
| `process_options`             | `array`      |          | `[]`    | **Deprecated**: any non-empty value throws an `\InvalidArgumentException`                           |

Examples
--------

* Import each file of a folder in parallel, 5 at a time

```yaml
# Task configuration level
parallel_import:
  service: '@CleverAge\ProcessBundle\Task\Process\ProcessLauncherTask'
  options:
    process: app.import_file
    max_processes: 5
    sleep_interval: 0.5
    sleep_interval_after_launch: 0.1
    context:
      mode: parallel
```

Notes
-----

* Sub-processes are run with the same Symfony environment as the current one (`--env`).
* The incremental error output (stderr) of the running sub-processes is echoed directly.
