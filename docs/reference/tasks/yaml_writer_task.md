YamlWriterTask
==============

Dumps the input as YAML to a file, whose path is set in the options. The file is overwritten on each execution.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Yaml\YamlWriterTask`

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
| `file_path` | `string` |  **X**   |         | Path of the file to write to. Its parent directory must exist          |
| `inline`    | `int`    |          | `4`     | Level at which the dumper switches to inline YAML (see `Yaml::dump()`) |

Examples
--------

```yaml
# Task configuration level
write_yaml:
  service: '@CleverAge\ProcessBundle\Task\File\Yaml\YamlWriterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/export.yaml'
    inline: 3
```

Notes
-----

* This task is not blocking: when receiving several inputs, each one overwrites the file. Aggregate the data first
  (e.g. with [AggregateIterableTask](aggregate_iterable_task.md)) if needed.
