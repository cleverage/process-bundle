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

| Command | Type | Required | Default | Description |
| ------- | ---- | :------: | ------- | ----------- |
| `file_path` | `string` | **X** |  | Path of the file to write to (relative to symfony root or absolute). It can also take two placeholders (`{date}` and `{date_time}`) to insert timestamps into the filename |
| `delimiter` | `string` |  | `;` | CSV delimiter |
| `enclosure` | `string` |  | `"` | CSV enclosure character |
| `escape` | `string` |  | `\\` | CSV escape character |
| `headers` | `array` or `null` |  | `null` | Static list of CSV headers, without the option, it will be dynamically read from first line |
| `mode` | `string` |  | `wb` | File open mode (see [fopen mode parameter](https://secure.php.net/manual/en/function.fopen.php)) |
| `split_character` | `string` |  | `\|` | Used to implode array values |
| `write_headers` | `bool` |  | `true` | Write the headers as a first line |
