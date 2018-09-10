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
    errors: <list of following task codes>
    error_strategy: <skip|stop>
    log_errors: <true|false>
```

Process attributes
------------------

**service**: reference service used for the task, must implement `CleverAge\ProcessBundle\Model\TaskInterface`

**description**: optional string to describe a task, displayed in process help (should not exceed one line)

**help**: optional string to describe in depth a task, displayed in verbose process help (can be multiline)

**options**: optional list of parameters to pass to a task

**outputs**: optional list of following tasks

**errors**: optional list of following tasks, in case of error

**error_strategy**: either *skip* (default) or *stop*, defines if a task can be continued or not

**log_errors**: optional boolean (defaults to true), to allow logging thrown errors
