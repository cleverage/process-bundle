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

**service**: required, reference to the service used for the task: a service **id** or a service **alias**, with or
without a leading `@` (quote the value when it starts with `@`, as YAML requires). The service must implement
`CleverAge\ProcessBundle\Model\TaskInterface`. For instance, the `UnzipTask` of the archive bridge is registered as
`cleverage_archive_process.task.unzip`, with an alias named after its class: these four references are equivalent.

```yaml
service: '@cleverage_archive_process.task.unzip'                  # id, with @
service: cleverage_archive_process.task.unzip                     # id, without @
service: '@CleverAge\ArchiveProcessBundle\Task\UnzipTask'         # alias, with @
service: CleverAge\ArchiveProcessBundle\Task\UnzipTask            # alias, without @
```

The tasks of this bundle and of the bridge bundles are registered with a `cleverage_process.task.*` /
`cleverage_*_process.task.*` id and an alias named after their class (e.g. `cleverage_process.task.transformer` and
`'@CleverAge\ProcessBundle\Task\TransformerTask'`, the class name being the reference used in this documentation), and
your own task services with any id or alias you choose. With an unknown service, a reference with `@` throws the
`ServiceNotFoundException` of the service container, a reference without `@` an `\UnexpectedValueException`
(`Unable to resolve service reference for Task '<task code>'`).

The services referenced by the process configurations are collected when the container is compiled, so they can be
private (as well as the aliases). Only a service that is not referenced by these configurations (e.g. a configuration
added at runtime) is fetched from the service container, which requires a public service and is deprecated since 5.4.

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
