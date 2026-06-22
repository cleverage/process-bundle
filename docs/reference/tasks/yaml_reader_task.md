YamlReaderTask
==============

Reads a YAML file and iterates over its root elements.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Yaml\YamlReaderTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`any`: each root element of the parsed YAML file, one per iteration

Options
-------

| Code        | Type     | Required | Default | Description                                                     |
|-------------|----------|:--------:|---------|-----------------------------------------------------------------|
| `file_path` | `string` | **X**    |         | Path to the YAML file to read. File must exist at resolve time. |

Example
-------

```yaml
# Task configuration level
read_yaml:
  service: '@CleverAge\ProcessBundle\Task\File\Yaml\YamlReaderTask'
  options:
    file_path: 'config/data.yaml'
  outputs: [process_item]
```
