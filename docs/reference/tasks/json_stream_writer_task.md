JsonStreamWriterTask
===============

Write given array to a json file, will wait until the end of the previous iteration (this is a blocking task) and outputs
the file path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamWriterTask`
* **Blocking task**

Accepted inputs
---------------

`array`

Possible outputs
----------------

`string`: absolute path of the produced file

Options
-------

| Code                    | Type            | Required | Default                                                                               | Description                                                                                                                                                                                       |
|-------------------------|-----------------|:--------:|---------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `file_path`             | `string`        |  **X**   |                                                                                       | Path of the file to write to (relative to symfony root or absolute).<br/>It can also take placeholders (`{date}`, `{date_time}`, `{timestamp}` `{unique_token}`) to insert data into the filename |
| `spl_file_object_flags` | `array`, `null` |          | `\SplFileObject::DROP_NEW_LINE \SplFileObject::READ_AHEAD \SplFileObject::SKIP_EMPTY` | Flags to pass to `SplFileObject` constructor, can be empty.<br/>See [PHP documentation](https://www.php.net/manual/en/splfileobject.construct.php) for more information on available flags.       |
| `json_flags`            | `array`, `null` |          | `\JSON_THROW_ON_ERROR`                                                                | Flags to pass to `json_encode` function, can be empty.<br/>See [PHP documentation](https://www.php.net/manual/en/function.json-encode.php) for more information on available flags.               |

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
  service: '@CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamWriterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/json_stream_writer_{date_time}.csv'
    spl_file_object_flags: []
    json_flags:
      - !php/const JSON_PRETTY_PRINT
      - !php/const JSON_UNESCAPED_SLASHES
```
