ColumnAggregatorTask
====================

For each configured column, collects the input rows that contain this column (and match the optional condition), and
outputs all the groups once all previous tasks are resolved.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ColumnAggregatorTask`
* **Blocking task**

Accepted inputs
---------------

`array`: an associative array that should contain the configured `columns` (a column whose value is `null` is
considered missing)

Possible outputs
----------------

`array`: associative array indexed by column **name**, each entry being:
* `<reference_key>`: the column name
* `<aggregation_key>`: list of the whole input rows that contain this column and matched the condition

Columns that never matched are absent; if nothing matched, the output is an empty array.

Options
-------

| Code              | Type     | Required | Default  | Description                                                                                                                                     |
|-------------------|----------|:--------:|----------|-------------------------------------------------------------------------------------------------------------------------------------------------|
| `columns`         | `array`  |  **X**   |          | List of column keys to aggregate on                                                                                                             |
| `reference_key`   | `string` |          | `column` | Key holding the column name in each output group                                                                                                |
| `aggregation_key` | `string` |          | `values` | Key holding the aggregated rows in each output group                                                                                            |
| `condition`       | `array`  |          | `[]`     | Conditions (`match`, `not_match`, `empty`, `not_empty`, `match_regexp`, `not_match_regexp`), see [ConditionTrait](../traits/condition_trait.md) |
| `ignore_missing`  | `bool`   |          | `false`  | If `true`, missing columns only log a warning instead of throwing an `\UnexpectedValueException`                                                |

The condition is checked, for each column, against an array with two keys: `input_column_value` (the value of the
column) and `input` (the whole row). Use property paths such as `[input_column_value]` or `[input][status]`.

Examples
--------

* Aggregate rows whose `col1` equals `A`

```yaml
# Task configuration level
iterate:
  service: '@CleverAge\ProcessBundle\Task\InputIteratorTask'
  outputs: [aggregate_a]
aggregate_a:
  service: '@CleverAge\ProcessBundle\Task\ColumnAggregatorTask'
  options:
    columns: [col1]
    condition:
      match:
        '[input_column_value]': A
```

With inputs `{col1: A, col2: 1}`, `{col1: B, col2: 2}` and `{col1: A, col2: 3}`, the output is
`{col1: {column: col1, values: [{col1: A, col2: 1}, {col1: A, col2: 3}]}}`.
