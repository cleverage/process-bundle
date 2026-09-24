Task Definition
===============

YAML Configuration
------------------

```yaml
<task_code>:
    service: <service reference>
    description: <string>
    help: <multiline string>
    options: <task options>
    outputs: <list of following task codes>
    error_outputs: <list of following task codes>
    errors: <list of following task codes> # Deprecated, use error_outputs
    error_strategy: <skip|stop>
    log_level: <emergency|alert|critical|error|warning|notice|info|debug>
```

Task attributes
---------------

**service**: required, reference to the service used for the task, with or without a leading `@`. The service must be
public and implement `CleverAge\ProcessBundle\Model\TaskInterface`.

**description**: optional string to describe a task, displayed in process help (should not exceed one line).

**help**: optional string to describe in depth a task (can be multiline).

**options**: optional list of parameters to pass to the task. String values can contain `{{ key }}` placeholders that
are replaced by the process [contextual values](../01-quick_start.md#contextual-values).

**outputs**: optional list of following tasks, receiving the output of this task. It can be a simple string.

**error_outputs**: optional list of following tasks, receiving the error output of this task (by default its input when
an error occurs). It can be a simple string. See [errors and skips](../04-advanced_workflow.md#errors-and-skips).

**errors**: deprecated alias of `error_outputs`. Defining both on the same task throws an exception.

**error_strategy**: optional, either *skip* or *stop*. Defines if the process continues with the next input or stops
when the task fails. When not defined, the global `default_error_strategy` is used (*stop* by default).

**log_level**: optional [RFC 5424](https://datatracker.ietf.org/doc/html/rfc5424) severity (emergency, alert, critical,
error, warning, notice, info, debug) of the log record written on the `cleverage_process_task` channel when the task
fails. Default is *critical*.
