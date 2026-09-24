InputFolderBrowserTask
======================

Browses a folder, whose path is given as input, recursively and iterates over each file found (sorted by name),
outputting its path. Same behaviour as [FolderBrowserTask](folder_browser_task.md), except for the folder path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\InputFolderBrowserTask`
* **Iterable task**
* **Flushable task**

Accepted inputs
---------------

`string`: path of the folder to browse, prefixed by the `base_folder_path` option. It must be an existing readable
directory.

Receiving a different folder path before the task has been flushed throws a `\LogicException`.

Possible outputs
----------------

`string`: path of the file (folder path followed by the path of the file relative to it).
Underlying component is [Symfony Finder](https://symfony.com/doc/current/components/finder.html).

If no file is found, a message is logged with the `empty_log_level` level, the task is skipped and the folder path is
set as error output.

Options
-------

| Code               | Type                  | Required | Default   | Description                                                                                                    |
|--------------------|-----------------------|:--------:|-----------|----------------------------------------------------------------------------------------------------------------|
| `base_folder_path` | `string`              |          | `''`      | Prepended to the input path, without adding any separator                                                      |
| `name_pattern`     | `string\|array\|null` |          | `null`    | Restrict files by name using a pattern (glob, regexp or string) or an array of patterns (see `Finder::name()`) |
| `empty_log_level`  | `string`              |          | `warning` | Log level used when no file is found, one of the `Psr\Log\LogLevel` constants                                  |

The `folder_path` option of [FolderBrowserTask](folder_browser_task.md) is removed: the path comes from the input.

Examples
--------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output: '/var/data'
  outputs: [directory]
directory:
  service: '@CleverAge\ProcessBundle\Task\File\InputFolderBrowserTask'
  options:
    base_folder_path: '%kernel.project_dir%'
  outputs: [read]
read:
  service: '@CleverAge\ProcessBundle\Task\File\InputFileReaderTask'
```
