YamlWriterTask
==============

Dumps the input as YAML to a file, whose path is set in the options. The file is overwritten on each execution.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Yaml\YamlWriterTask`
* **Code**: `yaml_writer` (`task: yaml_writer`)

Accepted inputs
---------------

`mixed`: data to dump, usually an `array`. Underlying method is Symfony `Yaml::dump()`.

Possible outputs
----------------

`string`: path of the written file (value of the `file_path` option).

Options
-------

| Code        | Type     | Required | Default | Description                                                            |
|-------------|----------|:--------:|---------|------------------------------------------------------------------------|
| `file_path` | `string` |  **X**   |         | Path of the file to write to. Its parent directory is created if needed |
| `inline`    | `int`    |          | `4`     | Level at which the dumper switches to inline YAML (see `Yaml::dump()`) |

Examples
--------

```yaml
# Task configuration level
write_yaml:
  task: yaml_writer
  options:
    file_path: '%kernel.project_dir%/var/data/export.yaml'
    inline: 3
```

Notes
-----

* This task is not blocking: when receiving several inputs, each one overwrites the file. Aggregate the data first
  (e.g. with [AggregateIterableTask](aggregate_iterable_task.md)) if needed.
* When the file cannot be written (e.g. not writable directory), the task fails with a `RuntimeException`.
