CommandRunnerTask
=================

Runs a system command (with the Symfony Process component) for each input. The input is passed to the command as
stdin, and the command standard output becomes the task output.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Process\CommandRunnerTask`

Accepted inputs
---------------

`string|int|float|bool|resource|\Traversable|null`: passed as stdin to the command (see `Process::setInput()`).

Possible outputs
----------------

`string`: the standard output of the command.

The command is run with `Process::mustRun()`: a non-zero exit code or a timeout throws an exception, handled
according to the task `error_strategy`.

Options
-------

| Code          | Type               | Required | Default                   | Description                                                                    |
|---------------|--------------------|:--------:|---------------------------|--------------------------------------------------------------------------------|
| `commandline` | `string\|array`    |  **X**   |                           | Command to run, as an array of arguments (recommended) or a string run by the shell (see Notes) |
| `cwd`         | `string\|null`     |          | Symfony project directory | Working directory of the command                                               |
| `env`         | `array\|null`      |          | `null`                    | Environment variables of the command (`null` inherits the current environment) |
| `timeout`     | `int\|float\|null` |          | `60`                      | Timeout in seconds (`null` disables it)                                        |
| `options`     | `array\|null`      |          | `null`                    | Passed to `Process::setOptions()`: `blocking_pipes`, `create_process_group`, `create_new_console` |

Examples
--------

* Count the lines of the input

```yaml
# Task configuration level
count_lines:
  service: '@CleverAge\ProcessBundle\Task\Process\CommandRunnerTask'
  options:
    commandline: ['wc', '-l']
    timeout: 30
  outputs: [next_task]
```

* Use shell features (pipes, environment variables)

```yaml
# Task configuration level
count_errors:
  service: '@CleverAge\ProcessBundle\Task\Process\CommandRunnerTask'
  options:
    commandline: 'grep "$PATTERN" | wc -l'
    env:
      PATTERN: 'error'
  outputs: [next_task]
```

Notes
-----

An array `commandline` is run with `new Process()`: each argument is escaped and no shell is involved. A string
`commandline` is run with `Process::fromShellCommandline()`: it is interpreted by the shell, so never build it from
untrusted input.
