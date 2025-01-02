InputLineReaderTask
=============

Reads a file and iterate on each line, returning content as string. Skips empty lines.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\InputLineReaderTask`
* **Iterable task**

Accepted inputs
---------------

`string`: file path

Possible outputs
----------------

`string`: foreach line, it will return content as string.
Underlying method is [SplFileObject](https://www.php.net/manual/en/class.splfileobject.php).

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
  service: '@CleverAge\ProcessBundle\Task\File\InputLineReaderTask'
```


