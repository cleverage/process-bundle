InputLineReaderTask
===================

Reads a file, whose path is given as input, and iterates over its lines, outputting each line as a string.
Same behaviour as [LineReaderTask](line_reader_task.md), except for the file path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\InputLineReaderTask`
* **Iterable task**

Accepted inputs
---------------

`string`: path of the file to read. An `\UnexpectedValueException` is thrown if the file does not exist or is not
readable. When a different path is received, the previous file is dropped and the new one is opened.

Possible outputs
----------------

`string`: for each line, its raw content. The line break is not removed.
Underlying class is [SplFileObject](https://www.php.net/manual/en/class.splfileobject.php), with the `READ_AHEAD` and
`SKIP_EMPTY` flags.

Options
-------

This task has no option.

Examples
--------

* Read every line of every file of a folder

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
  options:
    folder_path: '%kernel.project_dir%/var/data'
  outputs: [read]
read:
  service: '@CleverAge\ProcessBundle\Task\File\InputLineReaderTask'
  outputs: [log_line]
```
