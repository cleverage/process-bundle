InputCsvReaderTask
==================

Reads a CSV file whose path is given as input and iterates over its lines, outputting each line as an associative array
indexed by the CSV headers. Same behaviour as [CsvReaderTask](csv_reader_task.md), except for the file path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask`
* **Iterable task**

Accepted inputs
---------------

`string`: path of the file to read, prefixed by the `base_path` option if set.
When a different path is received, the previous file is dropped and the new one is opened.

Possible outputs
----------------

`array`: for each line, an associative array whose keys are the headers and values are strings.
Underlying method is [fgetcsv](https://www.php.net/manual/en/function.fgetcsv.php).

When no line can be read (typically the trailing empty line at the end of the file), no output is produced and the
task is skipped for this iteration.

Options
-------

| Code              | Type          | Required | Default | Description                                                                                                                       |
|-------------------|---------------|:--------:|---------|-----------------------------------------------------------------------------------------------------------------------------------|
| `base_path`       | `string`      |          | `''`    | Prepended (with a `/` separator) to the input path. If empty, the input path is used as is                                        |
| `delimiter`       | `string`      |          | `;`     | CSV delimiter                                                                                                                     |
| `enclosure`       | `string`      |          | `"`     | CSV enclosure character                                                                                                           |
| `escape`          | `string`      |          | `\`     | CSV escape character                                                                                                              |
| `headers`         | `array\|null` |          | `null`  | Static list of CSV headers. If `null`, headers are read from the first line of the file; otherwise the first line is read as data |
| `mode`            | `string`      |          | `rb`    | File open mode (see [fopen mode parameter](https://www.php.net/manual/en/function.fopen.php))                                     |
| `log_empty_lines` | `bool`        |          | `false` | Log a warning when a line cannot be read (empty line)                                                                             |

The `file_path` option of [CsvReaderTask](csv_reader_task.md) is removed: the path comes from the input.

Examples
--------

* Read every CSV file of a folder

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
  options:
    folder_path: '%kernel.project_dir%/var/data'
    name_pattern: '*.csv'
  outputs: [read]
read:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask'
  outputs: [dump]
```

* Read an uploaded file (process entry point), with a contextualized delimiter
  - the delimiter must be passed on execution: `-c delimiter:";"`

```yaml
# Task configuration level
read:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask'
  options:
    delimiter: '{{ delimiter }}'
  outputs: [dump]
```
