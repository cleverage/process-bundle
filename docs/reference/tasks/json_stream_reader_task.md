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

none

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
```


