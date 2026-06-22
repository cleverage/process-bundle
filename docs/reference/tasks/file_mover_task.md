FileMoverTask
=============

Move the file passed as input to a destination path. Supports overwrite and auto-increment to avoid collisions.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileMoverTask`

Accepted inputs
---------------

`string`: path to the file to move

Possible outputs
----------------

`string`: the final destination path

Options
-------

| Code            | Type      | Required | Default | Description                                                                                |
|-----------------|-----------|:--------:|---------|--------------------------------------------------------------------------------------------|
| `destination`   | `string`  | **X**    |         | Destination path (file or directory). If a directory, the original filename is preserved.   |
| `overwrite`     | `boolean` |          | `false` | Allow overwriting an existing file at destination                                          |
| `autoincrement` | `boolean` |          | `false` | If destination file exists, append an incremented suffix (e.g. `file-1.csv`, `file-2.csv`) |

Example
-------

```yaml
# Task configuration level
move_file:
  service: '@CleverAge\ProcessBundle\Task\File\FileMoverTask'
  options:
    destination: '/archive/processed/'
    autoincrement: true
```
