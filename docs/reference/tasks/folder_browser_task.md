FolderBrowserTask
=============

Reads a folder and iterate on each file, returning absolute path as string.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FolderBrowserTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`string`: absolute path of the file.
Underlying method is [Symfony Finder component](https://symfony.com/doc/current/components/finder.html).

Options
-------

| Code              | Type                        | Required  | Default                   | Description                                                                            |
|-------------------|-----------------------------|:---------:|---------------------------|----------------------------------------------------------------------------------------|
| `folder_path`     | `string`                    |   **X**   |                           | Path of the directory to read from                                                     |
| `name_pattern`    | `null`, `string` or `array` |           | null                      | Restrict files using a pattern (a regexp, a glob, or a string) or an array of patterns |
| `empty_log_level` | `string`                    |           | Psr\Log\LogLevel::WARNING | From Psr\Log\LogLevel constants                                                        |

Example
-------

```yaml
# Task configuration level
code:
  service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
  options:
    folder_path: '%kernel.project_dir%/var/data'
```


