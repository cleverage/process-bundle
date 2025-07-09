FileSplitterTask
=============

Split long file into smaller ones

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileSplitterTask`
* **Iterable task**

Accepted inputs
---------------

`array`: inputs are merged with task defined options.

Possible outputs
----------------

`string`: absolute path of the produced file

Options
-------

| Code                    | Type            | Required | Default  | Description                              |
|-------------------------|-----------------|:--------:|----------|------------------------------------------|
| `file_path`             | `string`        |  **X**   |          | Path of the file to read from (absolute) |
| `max_lines`             | `int`           |  **X**   | 1000     | Max number of line on a produced file    |

Example
-------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\FileSplitterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/json_stream_reader.json'
    max_lines: 1
```


