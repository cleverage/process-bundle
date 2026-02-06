FileWriterTask
==============

Write the input content to a file at the configured path.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileWriterTask`

Accepted inputs
---------------

`string`: content to write to the file

Possible outputs
----------------

`string`: the file path where the content was written

Options
-------

| Code       | Type     | Required | Default | Description             |
|------------|----------|:--------:|---------|-------------------------|
| `filename` | `string` | **X**    |         | Destination file path   |

Example
-------

```yaml
# Task configuration level
write_file:
  service: '@CleverAge\ProcessBundle\Task\File\FileWriterTask'
  options:
    filename: 'output/result.txt'
```
