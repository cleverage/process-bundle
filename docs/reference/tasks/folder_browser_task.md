FolderBrowserTask
=================

Browses a folder, whose path is set in the options, recursively and iterates over each file found (sorted by name),
outputting its path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FolderBrowserTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`string`: path of the file (`folder_path` followed by the path of the file relative to it).
Underlying component is [Symfony Finder](https://symfony.com/doc/current/components/finder.html).

If no file is found, a message is logged with the `empty_log_level` level, the task is skipped and the folder path is
set as error output.

Options
-------

| Code              | Type                  | Required | Default   | Description                                                                                                    |
|-------------------|-----------------------|:--------:|-----------|----------------------------------------------------------------------------------------------------------------|
| `folder_path`     | `string`              |  **X**   |           | Path of the folder to browse. Must be an existing readable directory (checked at initialization)               |
| `name_pattern`    | `string\|array\|null` |          | `null`    | Restrict files by name using a pattern (glob, regexp or string) or an array of patterns (see `Finder::name()`) |
| `empty_log_level` | `string`              |          | `warning` | Log level used when no file is found, one of the `Psr\Log\LogLevel` constants                                  |

Examples
--------

* Browse all CSV files of a folder

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
  options:
    folder_path: '%kernel.project_dir%/var/data'
    name_pattern: '*.csv'
    empty_log_level: info
  outputs: [read]
```

Notes
-----

* `current_file_path` is added to the error context of the process.
* See also [InputFolderBrowserTask](input_folder_browser_task.md) to browse a folder path given as input.
