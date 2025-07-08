JsonStreamReaderTask
=============

Reads a json file and iterate on each line, returning decoded content as array. Skips empty lines.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamReaderTask`
* **Iterable task**

Accepted inputs
---------------

`string`: Path of the file to read from (absolute)

Possible outputs
----------------

`array`: foreach line, it will return content as array.
Underlying method are [SplFileObject::fgets](https://www.php.net/manual/fr/splfileobject.fgets.php) and [json_decode](https://www.php.net/manual/en/function.json-decode.php).

Options
-------

| Code                    | Type            | Required | Default                                                                               | Description                                                                                                                                                                                 |
|-------------------------|-----------------|:--------:|---------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `spl_file_object_flags` | `array`, `null` |          | `\SplFileObject::DROP_NEW_LINE \SplFileObject::READ_AHEAD \SplFileObject::SKIP_EMPTY` | Flags to pass to `SplFileObject` constructor, can be empty.<br/>See [PHP documentation](https://www.php.net/manual/en/splfileobject.construct.php) for more information on available flags. |
| `json_flags`            | `array`, `null` |          | `\JSON_THROW_ON_ERROR`                                                                | Flags to pass to `json_encode` function, can be empty.<br/>See [PHP documentation](https://www.php.net/manual/en/function.json-encode.php) for more information on available flags.         |


Example
-------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  outputs: read
  options:
    output:
      file_path: '%kernel.project_dir%/var/data/json_stream_reader.json'
read:
  service: '@CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamReaderTask'
  options:
    spl_file_object_flags: []
    json_flags:
      - !php/const JSON_ERROR_NONE
```


