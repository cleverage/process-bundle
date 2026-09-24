Process Definition
==================

YAML Configuration
------------------

```yaml
clever_age_process:
    default_error_strategy: <stop|skip>
    configurations:
        <process_code>:
            description: <string>
            help: <multiline string>
            entry_point: <task_code>
            end_point: <task_code>
            public: <true|false>
            options: <array>
            tasks:
                <task_code>: <task_definition>
```

Global attributes
-----------------

**default_error_strategy**: optional, either *stop* (default) or *skip*. Error strategy used by every task that does
not define its own `error_strategy` (see [task definition](02-task_definition.md)).

Process attributes
------------------

The process code (key of the `configurations` array) must be unique in the whole application: a process cannot be
defined twice, even in different files.

**description**: optional string to describe a process. Displayed in process list and help. Should not be too long
(~ one line). Default is empty.

**help**: optional string to describe in depth a process. Displayed in process help. Can be multiline. Default is
empty.

**entry_point**: optional task code (default is none) that will receive the process input (`--input` option of the
execute command, or `$input` argument of `ProcessManager::execute()`). The referenced task cannot have ancestors, and
must be in the [main branch](#main-branch) of the process.

**end_point**: optional task code (default is none) whose last output will be returned as the process output. It must
be in the main branch of the process.

**public**: optional boolean (default is true) to mark a process as public or private. Private processes are filtered
from the process list (unless `--all` is used) but execution is still allowed.

**options**: optional free array (default is empty), not used by this bundle itself. It is available to other bundles
through `ProcessConfiguration::getOptions()`, e.g. [cleverage/ui-process-bundle](https://github.com/cleverage/ui-process-bundle)
reads its `ui` key to configure the launch form.

**tasks**: list of task definitions contained in the process, indexed by task code. See
[task definition](02-task_definition.md).

### Main branch

Only the *main branch* of the process is executed: the group of connected tasks containing the `entry_point`, or else
the `end_point`, or else the first configured task. Other tasks are ignored with a warning (see
[orphan tasks](../04-advanced_workflow.md#orphan-tasks)).
