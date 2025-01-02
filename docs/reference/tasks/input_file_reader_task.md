InputFileReaderTask
=============

Reads a file and return raw content as a string

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\InputFileReaderTask`

Accepted inputs
---------------

`string`: file path

Possible outputs
----------------

`string`: raw content of the file.
Underlying method is [file_get_contents](https://www.php.net/manual/en/function.file-get-contents.php).

Options
-------

None

Example
-------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
  options:
    folder_path: '%kernel.project_dir%/var/data'
  outputs: read
read:
  service: '@CleverAge\ProcessBundle\Task\File\InputFileReaderTask'
```


