LoggerTask
==========

Logs a message with values read from the process state, then forwards the input unchanged.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Reporting\LoggerTask`

Accepted inputs
---------------

`mixed`

Possible outputs
----------------

`mixed`: the input, unchanged.

Options
-------

| Code        | Type           | Required | Default           | Description                                                                                                                 |
|-------------|----------------|:--------:|-------------------|-----------------------------------------------------------------------------------------------------------------------------|
| `level`     | `string`       |          | `debug`           | Log level (`Psr\Log\LogLevel` values)                                                                                       |
| `message`   | `string`       |          | `Log state input` | Log message                                                                                                                 |
| `context`   | `array`        |          | `['input']`       | List of property paths read on the `ProcessState` (e.g. `input`, `context`) and added to the log context under the same key |
| `reference` | `string\|null` |          | `null`            | If set, added to the log context as `reference`                                                                             |

Examples
--------

* Log a warning with the current input

```yaml
# Task configuration level
log:
  service: '@CleverAge\ProcessBundle\Task\Reporting\LoggerTask'
  options:
    level: warning
    message: DEMO LOGGER
  outputs: [next_task]
```

* Log the input and the process context with a reference

```yaml
# Task configuration level
log:
  service: '@CleverAge\ProcessBundle\Task\Reporting\LoggerTask'
  options:
    level: info
    message: Transformed
    context: [input, context]
    reference: '{{ file }}'
```
