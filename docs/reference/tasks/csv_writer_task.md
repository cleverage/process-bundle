CsvWriterTask
=============

Writes each received array as a line of a CSV file. As a blocking task, it waits until all inputs have been received
and then outputs the file path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask`
* **Blocking task**

Accepted inputs
---------------

`array`: an associative array whose keys match the headers and values are convertible to string. Values that are
arrays are imploded using the `split_character` option. Underlying method is
[fputcsv](https://www.php.net/manual/en/function.fputcsv.php).

Possible outputs
----------------

`string`: path of the written file (with placeholders replaced), once all inputs have been processed.

Options
-------

| Code              | Type          | Required | Default | Description                                                                                                                                                                                 |
|-------------------|---------------|:--------:|---------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `file_path`       | `string`      |  **X**   |         | Path of the file to write to (absolute or relative to the current working directory).<br/>Placeholders `{date}`, `{date_time}`, `{timestamp}` and `{unique_token}` are replaced in the path |
| `delimiter`       | `string`      |          | `;`     | CSV delimiter                                                                                                                                                                               |
| `enclosure`       | `string`      |          | `"`     | CSV enclosure character                                                                                                                                                                     |
| `escape`          | `string`      |          | `\`     | CSV escape character                                                                                                                                                                        |
| `headers`         | `array\|null` |          | `null`  | Static list of CSV headers. If `null`, the keys of the first input are used                                                                                                                 |
| `mode`            | `string`      |          | `wb`    | File open mode (see [fopen mode parameter](https://www.php.net/manual/en/function.fopen.php))                                                                                               |
| `split_character` | `string`      |          | `\|`    | Used to implode array values                                                                                                                                                                |
| `write_headers`   | `bool`        |          | `true`  | Write the headers as first line, only if the file is empty (useful with an append `mode`)                                                                                                   |

Examples
--------

* Write a CSV file with a date in its name

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  options:
    output:
      - column1: value1-1
        column2: value2-1
      - column1: value1-2
        column2: value2-2
  outputs: [write]
write:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/csv_writer_{date_time}.csv'
```

* Append lines to an existing file, with fixed headers

```yaml
# Task configuration level
write:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/export.csv'
    mode: 'ab'
    headers: [sku, name, price]
```

Notes
-----

* `{date}` is replaced by `Ymd`, `{date_time}` by `Ymd_His`, `{timestamp}` by the Unix timestamp and `{unique_token}`
  by a `uniqid()` value.
* The parent directory of the file is created if needed.
* Each input must contain every header key (extra keys are not allowed: the number of columns must match the number of
  headers), otherwise an `\UnexpectedValueException` is thrown. Columns are written in the headers order.
