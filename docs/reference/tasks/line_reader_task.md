LineReaderTask
==============

Reads a file, whose path is set in the options, and iterates over its lines, outputting each line as a string.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\LineReaderTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`string`: for each line, its raw content. The line break is not removed.
Underlying class is [SplFileObject](https://www.php.net/manual/en/class.splfileobject.php), with the `READ_AHEAD` and
`SKIP_EMPTY` flags.

Options
-------

| Code       | Type     | Required | Default | Description                                                                                                      |
|------------|----------|:--------:|---------|------------------------------------------------------------------------------------------------------------------|
| `filename` | `string` |  **X**   |         | Path of the file to read. An `\UnexpectedValueException` is thrown if the file does not exist or is not readable |

Examples
--------

* Read a file and remove the trailing line break of each line

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\LineReaderTask'
  options:
    filename: '%kernel.project_dir%/var/data/sample.txt'
  outputs: [trim]
trim:
  service: '@CleverAge\ProcessBundle\Task\TransformerTask'
  options:
    transformers:
      trim: ~
  outputs: [debug]
```

Notes
-----

* See also [InputLineReaderTask](input_line_reader_task.md) to read a file path given as input.
