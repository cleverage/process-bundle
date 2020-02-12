Process Definition
==================

YAML Configuration
------------------

```yaml
clever_age_process:
    configurations:
        <process_name>:
            description: <string>
            help: <multiline string>
            entry_point: <task_code>
            end_point: <task_code>
            public: <true|false>
            options: <deprecated array>
            tasks: 
                <task_definition>
```

Process attributes
------------------

**description**: optional string to describe a process. Displayed in process list and help. Should not be too long 
(~ one line).

**help**: optional string to describe in depth a process. Displayed in process help. Can be multiline.

**entry_point**: optional task code (default is none) that will receive the process input. The referenced task cannot have
ancestors.

**end_point**: optional task code (default is none) that will provide the process output

**public**: optional boolean (default is true) to mark a process as public or private. Private process are filtered from 
process list but execution is still allowed

**options**: deprecated variable node

**tasks**: list of task definitions contained in the process. See [task definition](02-task_definition.md)
