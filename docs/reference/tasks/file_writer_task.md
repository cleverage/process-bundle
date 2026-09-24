FileWriterTask
==============

Writes the input content to a file, whose path is set in the options. The file is overwritten on each execution.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileWriterTask`

Accepted inputs
---------------

`string`: content to write to the file.
Underlying method is Symfony `Filesystem::dumpFile()` (parent directories are created if needed).

Possible outputs
----------------

`string`: path of the written file (value of the `filename` option).

Options
-------

| Code       | Type     | Required | Default | Description               |
|------------|----------|:--------:|---------|---------------------------|
| `filename` | `string` |  **X**   |         | Path of the file to write |

Examples
--------

```yaml
# Task configuration level
write_file:
  service: '@CleverAge\ProcessBundle\Task\File\FileWriterTask'
  options:
    filename: '%kernel.project_dir%/var/data/result.txt'
```

Notes
-----

* This task is not blocking: when receiving several inputs, each one overwrites the file. Aggregate the data first
  (e.g. with [AggregateIterableTask](aggregate_iterable_task.md)) if needed.
