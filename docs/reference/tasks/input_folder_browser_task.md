InputFolderBrowserTask
=============

Reads a folder and iterate on each file, returning absolute path as string.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\InputFolderBrowserTask`
* **Iterable task**

Accepted inputs
---------------

`string`: folder path

Possible outputs
----------------

`string`: absolute path of the file.
Underlying method is [Symfony Finder component](https://symfony.com/doc/current/components/finder.html).

Options
-------

| Code               | Type     | Required  | Default | Description                           |
|--------------------|----------|:---------:|---------|---------------------------------------|
| `base_folder_path` | `string` |           |         | Concatenated with input `folder_path` |

Example
-------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output: '/var/data'
  outputs: directory
directory:
  service: '@CleverAge\ProcessBundle\Task\File\InputFolderBrowserTask'
  options:
    base_folder_path: '%kernel.project_dir%'
  outputs: read
```


