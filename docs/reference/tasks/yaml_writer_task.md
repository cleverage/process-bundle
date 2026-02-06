YamlWriterTask
==============

Writes the input data as a YAML file.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Yaml\YamlWriterTask`

Accepted inputs
---------------

`array`: data to dump as YAML

Possible outputs
----------------

`string`: the file path where the YAML was written

Options
-------

| Code        | Type      | Required | Default | Description                                                              |
|-------------|-----------|:--------:|---------|--------------------------------------------------------------------------|
| `file_path` | `string`  | **X**    |         | Destination file path                                                    |
| `inline`    | `integer` |          | `4`     | Level at which the dumper switches to inline YAML (see `Yaml::dump()`)   |

Example
-------

```yaml
# Task configuration level
write_yaml:
  service: '@CleverAge\ProcessBundle\Task\File\Yaml\YamlWriterTask'
  options:
    file_path: 'output/export.yaml'
    inline: 3
```
