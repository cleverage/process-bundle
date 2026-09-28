FileSplitterTask
================

Splits a long text file into smaller temporary files of at most `max_lines` lines. Iterates over the produced files
until the whole source file has been processed.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileSplitterTask`
* **Iterable task**

Accepted inputs
---------------

`array`: optional, merged over the task options (e.g. `{ file_path: ..., max_lines: ... }`), which allows to give the
file path as input. Any other input is ignored.

Possible outputs
----------------

`string`: path of a temporary file (created in the system temporary directory, with a `.tmp` extension) containing the
next `max_lines` lines of the source file (the last file may contain fewer lines).

Options
-------

| Code        | Type     | Required | Default | Description                               |
|-------------|----------|:--------:|---------|-------------------------------------------|
| `file_path` | `string` |  **X**   |         | Path of the file to split                 |
| `max_lines` | `int`    |          | `1000`  | Maximum number of lines per produced file |

Examples
--------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\FileSplitterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/json_stream_reader.json'
    max_lines: 100
  outputs: [read_chunk]
read_chunk:
  service: '@CleverAge\ProcessBundle\Task\File\InputLineReaderTask'
```

Notes
-----

* Values given as input are merged after option resolution, so they are not validated.
* Every line of the source file is kept, in order, including empty lines. Line content is preserved, but each line
  break (`\n` or `\r\n`) is written as `PHP_EOL`, and a missing line break on the last line is added.
* An empty source file produces no output (the task is skipped).
* Temporary files are not deleted by the task, use [FileRemoverTask](file_remover_task.md) if needed.
* For CSV files, prefer [CsvSplitterTask](csv_splitter_task.md) which keeps the headers in each produced file.
