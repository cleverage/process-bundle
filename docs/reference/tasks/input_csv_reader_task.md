InputCsvReaderTask
==================

Reads a CSV file whose path is given as input and iterates over its lines, outputting each line as an associative array
indexed by the CSV headers. Same behaviour as [CsvReaderTask](csv_reader_task.md), except for the file path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask`
* **Code**: `input_csv_reader` (`task: input_csv_reader`)
* **Iterable task**

Accepted inputs
---------------

`string`: path of the file to read, prefixed by the `base_path` option if set. Any other input (e.g. `null`) throws an
`UnexpectedValueException`.
The file is closed once fully read, so each input (the same path again or a different one) is read from its
beginning.

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
| `headers`         | `array\|null` |          | `null`  | Static list of CSV headers. If `null`, headers are read from the first line of the file; otherwise the first line is read as data. An empty list throws an `UnexpectedValueException` |
| `mode`            | `string`      |          | `rb`    | File open mode (see [fopen mode parameter](https://www.php.net/manual/en/function.fopen.php))                                     |
| `log_empty_lines` | `bool`        |          | `false` | Log a warning when an empty read ends the file (e.g. the line break at the end of the last line). A blank line in the middle of the file is not skipped: it fails with "Number of columns not matching" (unless the file has a single column) |

The `file_path` option of [CsvReaderTask](csv_reader_task.md) is removed: the path comes from the input.

Examples
--------

* Read every CSV file of a folder

```yaml
# Task configuration level
entry:
  task: folder_browser
  options:
    folder_path: '%kernel.project_dir%/var/data'
    name_pattern: '*.csv'
  outputs: [read]
read:
  task: input_csv_reader
  outputs: [dump]
```

* Read an uploaded file (process entry point), with a contextualized delimiter
  - the delimiter must be passed on execution: `-c delimiter:";"`

```yaml
# Task configuration level
read:
  task: input_csv_reader
  options:
    delimiter: '{{ delimiter }}'
  outputs: [dump]
```
