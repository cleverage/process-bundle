JsonStreamReaderTask
====================

Reads a JSON Lines file (one JSON document per line), whose path is given as input, and iterates over its lines,
outputting each decoded line.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamReaderTask`
* **Iterable task**

Accepted inputs
---------------

`string`: path of the file to read.

Possible outputs
----------------

`array`: for each line, the decoded JSON as an associative array.
Underlying methods are [SplFileObject::fgets](https://www.php.net/manual/en/splfileobject.fgets.php) and
[json_decode](https://www.php.net/manual/en/function.json-decode.php).

When a line is empty or decodes to `null`, no output is produced and the task is skipped for this iteration. Each
line must be a JSON object or array: a line decoding to a scalar (e.g. `42` or `"foo"`) raises a `\TypeError`.

Options
-------

| Code                    | Type          | Required | Default | Description                                                                                                                                                                                                                                            |
|-------------------------|---------------|:--------:|---------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `spl_file_object_flags` | `array\|null` |          | `null`  | List of `SplFileObject` flags, summed and passed to [SplFileObject::setFlags](https://www.php.net/manual/en/splfileobject.setflags.php).<br/>`null` means `DROP_NEW_LINE`, `READ_AHEAD` and `SKIP_EMPTY`; an empty array means no flag                 |
| `json_flags`            | `array\|null` |          | `null`  | List of JSON flags, summed and passed to [json_decode](https://www.php.net/manual/en/function.json-decode.php).<br/>`null` means `JSON_THROW_ON_ERROR`; an empty array means no flag (invalid lines are then skipped instead of throwing an exception) |

Examples
--------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output: '%kernel.project_dir%/var/data/json_stream_reader.json'
  outputs: [read]
read:
  service: '@CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamReaderTask'
  options:
    json_flags:
      - !php/const JSON_THROW_ON_ERROR
      - !php/const JSON_BIGINT_AS_STRING
  outputs: [dump]
```

Notes
-----

* Files written by [JsonStreamWriterTask](json_stream_writer_task.md) can be read by this task, as long as no flag
  producing multi-line JSON (like `JSON_PRETTY_PRINT`) was used.
