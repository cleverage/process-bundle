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
| `autoincrement` | `bool`   |          | `false` | If the destination file exists, append the first free numeric suffix to the file name, before its extension (e.g. `file-1.csv`, `file-2.csv`) |

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
* With `autoincrement`, only the file name is changed (never the directory part), and the suffix is always appended to
  the original name: `report-2024.csv` becomes `report-2024-1.csv`, `file` becomes `file-1`, `archive.tar.gz` becomes
  `archive.tar-1.gz` and a hidden file `.env` becomes `.env-1`.
