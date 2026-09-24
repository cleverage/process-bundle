InputFileReaderTask
===================

Reads the whole content of a file, whose path is given as input, and outputs it as a string.
Same behaviour as [FileReaderTask](file_reader_task.md), except for the file path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\InputFileReaderTask`

Accepted inputs
---------------

`string`: path of the file to read. An `\UnexpectedValueException` is thrown if the file does not exist or is not
readable.

Possible outputs
----------------

`string`: raw content of the file.
Underlying method is [file_get_contents](https://www.php.net/manual/en/function.file-get-contents.php).

Options
-------

This task has no option.

Examples
--------

* Read every file of a folder

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
  options:
    folder_path: '%kernel.project_dir%/var/data'
  outputs: [read]
read:
  service: '@CleverAge\ProcessBundle\Task\File\InputFileReaderTask'
  outputs: [debug]
```
