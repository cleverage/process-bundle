CsvReaderTask
=============

Reads a CSV file, whose path is set in the options, and iterates over its lines, outputting each line as an associative
array indexed by the CSV headers.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

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
| `file_path`       | `string`      |  **X**   |         | Path of the file to read from (absolute or relative to the current working directory)                                             |
| `delimiter`       | `string`      |          | `;`     | CSV delimiter                                                                                                                     |
| `enclosure`       | `string`      |          | `"`     | CSV enclosure character                                                                                                           |
| `escape`          | `string`      |          | `\`     | CSV escape character                                                                                                              |
| `headers`         | `array\|null` |          | `null`  | Static list of CSV headers. If `null`, headers are read from the first line of the file; otherwise the first line is read as data |
| `mode`            | `string`      |          | `rb`    | File open mode (see [fopen mode parameter](https://www.php.net/manual/en/function.fopen.php))                                     |
| `log_empty_lines` | `bool`        |          | `false` | Log a warning when a line cannot be read (empty line)                                                                             |

Examples
--------

* Read a CSV file with a contextualized delimiter
  - the delimiter must be passed on execution: `-c delimiter:";"`

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask'
  options:
    file_path: '%kernel.project_dir%/var/data/sample.csv'
    delimiter: '{{ delimiter }}'
  outputs: [log_line]
```

* Read a CSV file without header line

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask'
  options:
    file_path: '%kernel.project_dir%/var/data/no_header.csv'
    delimiter: ','
    headers: [sku, name, price]
  outputs: [transform]
```

Notes
-----

* Each line must contain exactly as many columns as there are headers, otherwise an `\UnexpectedValueException` is thrown.
* A UTF-8 BOM is removed from the first header when headers are read from the file.
* `csv_file` and `csv_line` are added to the error context of the process.
* See also [InputCsvReaderTask](input_csv_reader_task.md) to read a file path given as input.
