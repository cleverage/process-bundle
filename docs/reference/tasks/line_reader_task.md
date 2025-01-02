LineReaderTask
=============

Reads a file and iterate on each line, returning content as string. Skips empty lines.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\LineReaderTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`string`: foreach line, it will return content as string.
Underlying method is [SplFileObject](https://www.php.net/manual/en/class.splfileobject.php).

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
  service: '@CleverAge\ProcessBundle\Task\File\LineReaderTask'
  options:
    filename: 'path/to/file.txt'
```


