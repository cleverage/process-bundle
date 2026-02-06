CsvSplitterTask
===============

Split a large CSV file into smaller temporary CSV files, keeping the headers in each resulting file. Iterates over
chunks until the entire source file has been processed.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Csv\CsvSplitterTask`
* **Iterable task**
* **Finalizable task**

Accepted inputs
---------------

Input is ignored (file path comes from options or parent class behavior)

Possible outputs
----------------

`string`: path to a temporary CSV file containing up to `max_lines` rows (plus headers)

Options
-------

Inherits all options from [InputCsvReaderTask](input_csv_reader_task.md), plus:

| Code        | Type      | Required | Default | Description                                     |
|-------------|-----------|:--------:|---------|-------------------------------------------------|
| `max_lines` | `integer` |          | `1000`  | Maximum number of data rows per split file      |

Example
-------

```yaml
# Task configuration level
split_csv:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvSplitterTask'
  options:
    file_path: 'data/large_file.csv'
    delimiter: ';'
    max_lines: 500
  outputs: [process_chunk]
```

Each iteration outputs a temporary file path containing at most 500 rows from the source CSV.
