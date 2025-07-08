JsonStreamWriterTask
===============

Write given array to a json file, will wait until the end of the previous iteration (this is a blocking task) and outputs
the file path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamWriterTask`
* **Blocking task**

Accepted inputs
---------------

`array`

Possible outputs
----------------

`string`: absolute path of the produced file

Options
-------

| Code        | Type     | Required | Default | Description                                                                                                                                                                                       |
|-------------|----------|:--------:|---------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `file_path` | `string` |  **X**   |         | Path of the file to write to (relative to symfony root or absolute).<br/>It can also take placeholders (`{date}`, `{date_time}`, `{timestamp}` `{unique_token}`) to insert data into the filename |

Example
----------------

```yaml
# Task configuration level
entry:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  outputs: [ write ]
  options:
    output:
      - column1: value1-1
        column2: value2-1
        column3: value3-1
      - column1: value1-2
        column2: value2-2
        column3: value3-2
      - column1: ''
        column2: null
        column3: value3-3
write:
  service: '@CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamWriterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/json_stream_writer_{date_time}.csv'
```
