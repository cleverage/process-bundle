FileMoverTask
=============

Moves (renames) the file passed as input to a destination path. Supports overwriting and auto-incrementing the file
name to avoid collisions.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileMoverTask`

Accepted inputs
---------------

`string`: path of the file to move. An `\UnexpectedValueException` is thrown if it does not exist.

Possible outputs
----------------

`string`: the final destination path of the file.

Options
-------

| Code            | Type     | Required | Default | Description                                                                                                              |
|-----------------|----------|:--------:|---------|--------------------------------------------------------------------------------------------------------------------------|
| `destination`   | `string` |  **X**   |         | Destination path. If it is an existing directory, the original file name is kept                                         |
| `overwrite`     | `bool`   |          | `false` | Allow overwriting an existing file at destination (otherwise an exception is thrown)                                     |
| `autoincrement` | `bool`   |          | `false` | If the destination file exists, add or increment a numeric suffix before the extension (e.g. `file-1.csv`, `file-2.csv`) |

Examples
--------

* Archive processed files

```yaml
# Task configuration level
move_file:
  service: '@CleverAge\ProcessBundle\Task\File\FileMoverTask'
  options:
    destination: '%kernel.project_dir%/var/data/archive/'
    autoincrement: true
```

Notes
-----

* Underlying method is Symfony `Filesystem::rename()`.
