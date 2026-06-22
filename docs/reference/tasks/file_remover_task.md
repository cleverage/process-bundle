FileRemoverTask
===============

Simply delete the file or directory passed as input.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileRemoverTask`

Accepted inputs
---------------

`string`: path to the file or directory to remove

Possible outputs
----------------

No output is set.

Example
-------

```yaml
# Task configuration level
cleanup:
  service: '@CleverAge\ProcessBundle\Task\File\FileRemoverTask'
```
