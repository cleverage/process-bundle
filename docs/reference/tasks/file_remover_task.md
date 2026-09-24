FileRemoverTask
===============

Deletes the file(s) or directory(ies) passed as input. Directories are removed recursively.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileRemoverTask`

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
  service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
  options:
    folder_path: '%kernel.project_dir%/var/tmp'
  outputs: [cleanup]
cleanup:
  service: '@CleverAge\ProcessBundle\Task\File\FileRemoverTask'
```
