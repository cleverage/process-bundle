FileReaderTask
==============

Reads the whole content of a file, whose path is set in the options, and outputs it as a string.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileReaderTask`

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`string`: raw content of the file.
Underlying method is [file_get_contents](https://www.php.net/manual/en/function.file-get-contents.php).

Options
-------

| Code       | Type     | Required | Default | Description                                                                                                      |
|------------|----------|:--------:|---------|------------------------------------------------------------------------------------------------------------------|
| `filename` | `string` |  **X**   |         | Path of the file to read. An `\UnexpectedValueException` is thrown if the file does not exist or is not readable |

Examples
--------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\FileReaderTask'
  options:
    filename: '%kernel.project_dir%/var/data/sample.txt'
  outputs: [debug]
```

Notes
-----

* See also [InputFileReaderTask](input_file_reader_task.md) to read a file path given as input.
