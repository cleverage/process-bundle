Task Definition
===============

YAML Configuration
------------------

```yaml
<task_name>:                       # name of the task in the process, used in the outputs
    task: <code of the task>       # service: <service reference> is deprecated
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

**task**: required, code of the task, e.g. `task: transformer`. The code of each task of this bundle is given on its
[reference page](tasks/) (e.g. `transformer` for the [TransformerTask](tasks/transformer_task.md)); the code of a task
service is declared with a `cleverage.task` tag with a `code` attribute, or with the
`#[CleverAge\ProcessBundle\Attribute\AsTask(code: '...')]` attribute (see
[custom tasks](../03-custom_tasks.md#task-code)). A code is **unique**: it identifies a single task service in the
application (the tasks of this bundle have unprefixed codes, use a prefix for the ones of your own tasks), two services
with the same code fail the container compilation.
The codes are resolved when the container is compiled: an unknown code fails the compilation, with the list of the
known codes. The services of the tasks referenced by the process
configurations are collected when the container is compiled, so they can be private.

**service**: **deprecated since 5.4, removed in v6.0**, use `task`. Reference to the service used for the task, instead
of its code: its service id, with or without a leading `@` (quote the value when it starts with `@`, as YAML requires),
e.g. `service: '@CleverAge\ProcessBundle\Task\TransformerTask'` (the tasks of this bundle have their class name as
service id). The service must implement `CleverAge\ProcessBundle\Model\TaskInterface`. With an unknown service, a
reference with `@` throws the `ServiceNotFoundException` of the service container, a reference without `@` an
`\UnexpectedValueException` (`Unable to resolve service reference for Task '<task code>'`). A task defines either `task`
or `service`, not both.

Only a service that is not referenced by the configurations known when the container is compiled (e.g. a configuration
added at runtime) is fetched from the service container, which requires a public service and is deprecated since 5.4.

**description**: optional string to describe a task, displayed in process help (should not exceed one line).

**help**: optional string to describe in depth a task (can be multiline).

**options**: optional list of parameters to pass to the task. String values can contain `{{ key }}` placeholders that
are replaced by the process [contextual values](../01-quick_start.md#contextual-values).

**outputs**: optional list of following tasks, receiving the output of this task. It can be a simple string.

**error_outputs**: optional list of following tasks, receiving the error output of this task (by default its input when
an error occurs). It can be a simple string. See [errors and skips](../04-advanced_workflow.md#errors-and-skips).

**errors**: **deprecated since 5.4, removed in v6.0**, use `error_outputs` (same behaviour). Defining both on the same
task throws an exception.

**error_strategy**: optional, either *skip* or *stop*. Defines if the process continues with the next input or stops
when the task fails. When not defined, the global `default_error_strategy` is used (*stop* by default). Another value
is deprecated since 5.4 (`E_USER_DEPRECATED` error when the container is compiled; the process fails with "Unknown
error strategy" when the task fails), it will be rejected in v6.0.

**log_level**: optional [RFC 5424](https://datatracker.ietf.org/doc/html/rfc5424) severity (emergency, alert, critical,
error, warning, notice, info, debug) of the log record written on the `cleverage_process_task` channel when the task
fails. Default is *critical*.
