CsvReaderTask
=============

Reads a CSV file and iterate on each line, returning an array of key -> values. Skips empty lines.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`array`: foreach line, it will return a php array where key comes from headers and values are strings.
Underlying method is [fgetcsv](https://secure.php.net/manual/en/function.fgetcsv.php).

Options
-------

| Command | Type | Required | Default | Description |
| ------- | ---- | :------: | ------- | ----------- |
| `file_path` | `string` | **X** |  | Path of the file to read from (relative to symfony root or absolute) |
| `delimiter` | `string` |  | `;` | CSV delimiter |
| `enclosure` | `string` |  | `"` | CSV enclosure character |
| `escape` | `string` |  | `\\` | CSV escape character |
| `headers` | `array` or `null` |  | `null` | Static list of CSV headers, without the option, it will be dynamically read from first input |
| `mode` | `string` |  | `rb` | File open mode (see [fopen mode parameter](https://secure.php.net/manual/en/function.fopen.php)) |
| `log_empty_lines` | `bool` |  | `false` | Log when the output is empty |
