YamlReaderTask
==============

Parses a YAML file, whose path is set in the options, and iterates over its root elements.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Yaml\YamlReaderTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`mixed`: the value of each root element of the parsed file, one per iteration (keys are not output).
Underlying method is Symfony `Yaml::parseFile()`.

Options
-------

| Code        | Type     | Required | Default | Description                                                                                                          |
|-------------|----------|:--------:|---------|----------------------------------------------------------------------------------------------------------------------|
| `file_path` | `string` |  **X**   |         | Path of the YAML file to read. An `\UnexpectedValueException` is thrown at initialization if the file does not exist |

Examples
--------

```yaml
# Task configuration level
read_yaml:
  service: '@CleverAge\ProcessBundle\Task\File\Yaml\YamlReaderTask'
  options:
    file_path: '%kernel.project_dir%/var/data/data.yaml'
  outputs: [process_item]
```

Notes
-----

* The root of the file must be a mapping or a sequence, otherwise an `\InvalidArgumentException` is thrown (e.g. for an
  empty file). An empty root mapping or sequence (`{}` or `[]`) raises a `\TypeError`.
* The current root key is added to the error context of the process as `iterator_key`.
