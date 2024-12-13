InputCsvReaderTask
=============

Reads a CSV file and iterate on each line, returning an array of key -> values. Skips empty lines.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask`
* **Iterable task**

Accepted inputs
---------------

`string`: file path

Possible outputs
----------------

`array`: foreach line, it will return a php array where key comes from headers and values are strings.
Underlying method is [fgetcsv](https://secure.php.net/manual/en/function.fgetcsv.php).

Options
-------

Same as [CsvReaderTask](reference/tasks/csv_reader_task.md) except following :

| Code        | Type     | Required | Default | Description                |
|-------------|----------|:--------:|---------|----------------------------|
| `file_path` |          |          |         | Removed, use input instead |
| `base_path` | `string` |          | ``      |                            |

Example
-------

```yaml
clever_age_process:
  configurations:
    process.name:
      entry_point: entrypoint # for upload_and_run process entry_point is required
      tasks:
        entrypoint:
          service: '@CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask'
          options:
            delimiter: '{{ delimiter }}' ## delimiter is contextualized you must add -c delimiter:";" on console execute
```
