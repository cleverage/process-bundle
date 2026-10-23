Process Definition
==================

YAML Configuration
------------------

```yaml
clever_age_process:
    default_error_strategy: <stop|skip>
    logs:
        success_level: <emergency|alert|critical|error|warning|notice|info|debug>
        failed_level: <emergency|alert|critical|error|warning|notice|info|debug>
    configurations:
        <process_code>:
            description: <string>
            help: <multiline string>
            entry_point: <task_code>
            end_point: <task_code>
            public: <true|false>
            options: <array>
            logs:
                success_level: <emergency|alert|critical|error|warning|notice|info|debug>
                failed_level: <emergency|alert|critical|error|warning|notice|info|debug>
            tasks:
                <task_code>: <task_definition>
```

Global attributes
-----------------

**default_error_strategy**: optional, either *stop* (default) or *skip*. Error strategy used by every task that does
not define its own `error_strategy` (see [task definition](02-task_definition.md)).

**logs**: optional [RFC 5424](https://datatracker.ietf.org/doc/html/rfc5424) severities of the logs written at the end
of each process (channel `cleverage_process`, with the `duration` of the process in seconds in the record context):
* `success_level` (default *info*): level of the `Process <process_code> succeed` log, and of the
  `Process <process_code> stopped` log of a process stopped on purpose by a [StopTask](tasks/stop_task.md)
* `failed_level` (default *debug*): level of the `Process <process_code> failed` log, written when the process fails
  (in addition to the `Critical process failure` log)

Without UI, the success log is the only trace of a succeeded process: lower its level (e.g. *debug*) only if processes
are followed by other means.

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
from the process list (unless `--all` is used) but execution is still allowed. A value other than a boolean is rejected
when the container is compiled.

**options**: optional free array (default is empty), not used by this bundle itself. It is available to other bundles
through `ProcessConfiguration::getOptions()`, e.g. [cleverage/ui-process-bundle](https://github.com/cleverage/ui-process-bundle)
reads its `ui` key to configure the launch form.

**logs**: optional `success_level` and `failed_level` overriding, for this process only, the global
[`logs`](#global-attributes) levels (e.g. a scheduled process that often has nothing to do).

**tasks**: list of task definitions contained in the process, indexed by task code. See
[task definition](02-task_definition.md).

### Invalid task references

The task codes referenced by the `entry_point` and the `end_point` of the process, and by the `outputs` and the
`error_outputs` of its tasks, must be tasks of the process. They are not checked when the container is compiled, but
when the process configuration is resolved: getting or executing the process throws an
`InvalidProcessConfigurationException` naming the process, the unknown task and where it is referenced (e.g.
`Unknown task 'missing' in the 'outputs' of the task 'read' (from process: app.import)`).

Such a process, as any process whose configuration is not valid, is left out of the lists of processes
(`cleverage:process:list`, `ProcessConfigurationRegistryInterface::getProcessConfigurations()` used e.g. by the UI),
with an error log on the `cleverage_process` channel, instead of making them fail.

### Main branch

Only the *main branch* of the process is executed: the group of connected tasks containing the `entry_point`, or else
the `end_point`, or else the first configured task. Other tasks are ignored with a warning (see
[orphan tasks](../04-advanced_workflow.md#orphan-tasks)).
