LineReaderTask
==============

Reads a file, whose path is set in the options, and iterates over its lines, outputting each line as a string.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\LineReaderTask`
* **Code**: `line_reader` (`task: line_reader`)
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`string`: for each line, its raw content. The line break is not removed.
Underlying class is [SplFileObject](https://www.php.net/manual/en/class.splfileobject.php), with the `READ_AHEAD` and
`SKIP_EMPTY` flags. Without `DROP_NEW_LINE`, the lines keep their line break, so an empty line is output as `"\n"`
(only the empty string after the last line break is skipped).

Options
-------

| Code       | Type     | Required | Default | Description                                                                                                      |
|------------|----------|:--------:|---------|------------------------------------------------------------------------------------------------------------------|
| `filename` | `string` |  **X**   |         | Path of the file to read. An `UnexpectedValueException` is thrown if the file does not exist or is not readable |

Examples
--------

* Read a file and remove the trailing line break of each line

```yaml
# Task configuration level
entry:
  task: line_reader
  options:
    filename: '%kernel.project_dir%/var/data/sample.txt'
  outputs: [trim]
trim:
  task: transformer
  options:
    transformers:
      trim: ~
  outputs: [debug]
```

Notes
-----

* The file is released once fully read: if the task is executed again (e.g. for a new input), the file is read again
  from its beginning.
* See also [InputLineReaderTask](input_line_reader_task.md) to read a file path given as input.
