FileRemoverTask
===============

Deletes the file(s) or directory(ies) passed as input. Directories are removed recursively.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileRemoverTask`
* **Code**: `file_remover` (`task: file_remover`)

Accepted inputs
---------------

`string|iterable`: path, or list of paths, of files or directories to remove.
Underlying method is Symfony `Filesystem::remove()`: paths that do not exist are ignored.

Possible outputs
----------------

No output is set.

Options
-------

This task has no option.

Examples
--------

* Remove each file once it has been processed

```yaml
# Task configuration level
entry:
  task: folder_browser
  options:
    folder_path: '%kernel.project_dir%/var/tmp'
  outputs: [cleanup]
cleanup:
  task: file_remover
```
