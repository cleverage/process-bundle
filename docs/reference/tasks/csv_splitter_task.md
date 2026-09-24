CsvSplitterTask
===============

Splits a large CSV file, whose path is given as input, into smaller temporary CSV files, keeping the headers in each
of them. Iterates over the chunks until the whole source file has been processed.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Csv\CsvSplitterTask`
* **Iterable task**

Accepted inputs
---------------

`string`: path of the CSV file to split, prefixed by the `base_path` option if set
(same as [InputCsvReaderTask](input_csv_reader_task.md)).

Possible outputs
----------------

`string`: path of a temporary CSV file (created in the system temporary directory) containing the headers and a chunk
of lines of the source file.

Options
-------

| Code              | Type          | Required | Default | Description                                                                                          |
|-------------------|---------------|:--------:|---------|------------------------------------------------------------------------------------------------------|
| `max_lines`       | `int`         |          | `1000`  | Maximum number of lines per produced file (see notes)                                                |
| `base_path`       | `string`      |          | `''`    | Prepended (with a `/` separator) to the input path. If empty, the input path is used as is           |
| `delimiter`       | `string`      |          | `;`     | CSV delimiter (used for both the source and the produced files)                                      |
| `enclosure`       | `string`      |          | `"`     | CSV enclosure character                                                                              |
| `escape`          | `string`      |          | `\`     | CSV escape character                                                                                 |
| `headers`         | `array\|null` |          | `null`  | Static list of CSV headers. If `null`, headers are read from the first line of the source file       |
| `mode`            | `string`      |          | `rb`    | Source file open mode (see [fopen mode parameter](https://www.php.net/manual/en/function.fopen.php)) |
| `log_empty_lines` | `bool`        |          | `false` | Inherited from [CsvReaderTask](csv_reader_task.md), not used by this task                            |

Examples
--------

* Split a CSV file into chunks, then read each chunk

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output: '%kernel.project_dir%/var/data/large_file.csv'
  outputs: [split]
split:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvSplitterTask'
  options:
    delimiter: ';'
    max_lines: 500
  outputs: [read_chunk]
read_chunk:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask'
  options:
    delimiter: ';'
```

Notes
-----

* Lines are copied as is: they are not checked against the headers.
* Temporary files are not deleted by the task, use [FileRemoverTask](file_remover_task.md) if needed.
* The line counter of the produced file includes the header line and starts at 1, so each produced file actually
  contains `max_lines - 2` data lines.
