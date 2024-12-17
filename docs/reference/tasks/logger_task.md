LoggerTask
=============

Log a specific message with context.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Reporting\LoggerTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any` : forwarded input

Options
-------

| Code        | Type               | Required  | Default           | Description                     |
|-------------|--------------------|:---------:|-------------------|---------------------------------|
| `level`     | `string`           |   **X**   | `debug`           | Use `Psr\Log\LogLevel` values   |
| `message`   | `string`           |           | `Log state input` |                                 |
| `context`   | `array`            |           | `['input']`       |                                 |
| `reference` | `string` or `null` |           | `null`            | Override `context['reference']` |

Example
-------

```yaml
# Task configuration level
code:
  service: '@CleverAge\ProcessBundle\Task\Reporting\LoggerTask'
  options:
    level: warning
    message: DEMO LOGGER
```
