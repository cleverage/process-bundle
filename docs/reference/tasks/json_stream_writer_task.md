JsonStreamWriterTask
====================

Writes each received array as a JSON document on its own line (JSON Lines format), in a file whose path is set in the
options. As a blocking task, it waits until all inputs have been received and then outputs the file path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamWriterTask`
* **Blocking task**

Accepted inputs
---------------

`array`: data to encode. An `\UnexpectedValueException` is thrown for any other type.
Underlying method is [json_encode](https://www.php.net/manual/en/function.json-encode.php).

Possible outputs
----------------

`string`: path of the written file (with placeholders replaced), once all inputs have been processed.

Options
-------

| Code                    | Type          | Required | Default | Description                                                                                                                                                                                                                            |
|-------------------------|---------------|:--------:|---------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `file_path`             | `string`      |  **X**   |         | Path of the file to write to, opened in `wb` mode (overwritten).<br/>Placeholders `{date}`, `{date_time}`, `{timestamp}` and `{unique_token}` are replaced in the path                                                                 |
| `spl_file_object_flags` | `array\|null` |          | `null`  | List of `SplFileObject` flags, summed and passed to [SplFileObject::setFlags](https://www.php.net/manual/en/splfileobject.setflags.php).<br/>`null` means `DROP_NEW_LINE`, `READ_AHEAD` and `SKIP_EMPTY`; an empty array means no flag |
| `json_flags`            | `array\|null` |          | `null`  | List of JSON flags, summed and passed to [json_encode](https://www.php.net/manual/en/function.json-encode.php).<br/>`null` means `JSON_THROW_ON_ERROR`; an empty array means no flag                                                   |

Examples
--------

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
  service: '@CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamWriterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/json_stream_writer_{date_time}.json'
    json_flags:
      - !php/const JSON_THROW_ON_ERROR
      - !php/const JSON_UNESCAPED_SLASHES
      - !php/const JSON_UNESCAPED_UNICODE
```

Notes
-----

* `{date}` is replaced by `Ymd`, `{date_time}` by `Ymd_His`, `{timestamp}` by the Unix timestamp and `{unique_token}`
  by a `uniqid()` value.
* The parent directory of the file must exist.
* Using `JSON_PRETTY_PRINT` produces multi-line documents: the file can no longer be read by
  [JsonStreamReaderTask](json_stream_reader_task.md).
