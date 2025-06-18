CsvWriterTask
=============

Write given array to a CSV file, will wait until the end of the previous iteration (this is a blocking task) and outputs
the file path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask`
* **Blocking task**

Accepted inputs
---------------

`array`: foreach line, it will need a php array where key match the headers and values are convertible to string.
Underlying method is [fputcsv](https://secure.php.net/manual/en/function.fputcsv.php).

Possible outputs
----------------

`string`: absolute path of the produced file

Options
-------

| Code              | Type              | Required | Default | Description                                                                                                                                                                                       |
|-------------------|-------------------|:--------:|---------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `file_path`       | `string`          |  **X**   |         | Path of the file to write to (relative to symfony root or absolute).<br/>It can also take placeholders (`{date}`, `{date_time}`, `{timestamp}` `{unique_token}`) to insert data into the filename |
| `delimiter`       | `string`          |          | `;`     | CSV delimiter                                                                                                                                                                                     |
| `enclosure`       | `string`          |          | `"`     | CSV enclosure character                                                                                                                                                                           |
| `escape`          | `string`          |          | `\\`    | CSV escape character                                                                                                                                                                              |
| `headers`         | `array` or `null` |          | `null`  | Static list of CSV headers, without the option, it will be dynamically read from first line                                                                                                       |
| `mode`            | `string`          |          | `wb`    | File open mode (see [fopen mode parameter](https://secure.php.net/manual/en/function.fopen.php))                                                                                                  |
| `split_character` | `string`          |          | `\|`    | Used to implode array values                                                                                                                                                                      |
| `write_headers`   | `bool`            |          | `true`  | Write the headers as a first line                                                                                                                                                                 |

Example
----------------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  outputs: [ write ]
  options:
    output:
      - column1: value1-1
        column2: value2-1
        column3: value3-1
      - column1: value1-2
        column2: value2-2
        column3: value3-2
      - column1: ''
        column2: null
        column3: value3-3
write:
  service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/csv_writer_{date_time}.csv'
```
