FileReaderTask
=============

Reads a file and return raw content as a string

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\FileReaderTask`

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`string`: raw content of the file.
Underlying method is [file_get_contents](https://www.php.net/manual/en/function.file-get-contents.php).

Options
-------

| Code       | Type     | Required  | Default  | Description                              |
|------------|----------|:---------:|----------|------------------------------------------|
| `filename` | `string` |   **X**   |          | Path of the file to read from (absolute) |

Example
-------

```yaml
# Task configuration level
code:
  service: '@CleverAge\ProcessBundle\Task\File\FileReaderTask'
  options:
    filename: 'path/to/file.txt'
```


