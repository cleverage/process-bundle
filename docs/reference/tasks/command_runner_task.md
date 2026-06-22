CommandRunnerTask
=================

Launch a system command for each input. The input is passed as stdin to the command, and the command's stdout is
set as the task output.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Process\CommandRunnerTask`

Accepted inputs
---------------

`string|null`: data passed as stdin to the command

Possible outputs
----------------

`string`: the stdout of the executed command

Options
-------

| Code          | Type             | Required | Default                    | Description                                             |
|---------------|------------------|:--------:|----------------------------|---------------------------------------------------------|
| `commandline` | `string\|array`  | **X**    |                            | The command to execute (string or array of arguments)   |
| `cwd`         | `string`         |          | Symfony project directory  | Working directory for the command                       |
| `env`         | `array\|null`    |          | `null`                     | Environment variables (inherits current env if `null`)  |
| `timeout`     | `integer`        |          | `60`                       | Timeout in seconds                                      |

Example
-------

```yaml
# Task configuration level
run_command:
  service: '@CleverAge\ProcessBundle\Task\Process\CommandRunnerTask'
  options:
    commandline: ['wc', '-l']
    timeout: 30
```
