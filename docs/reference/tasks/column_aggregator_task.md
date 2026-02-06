ColumnAggregatorTask
====================

Aggregate input rows by column. For each configured column, inputs matching a condition are grouped together. The
result is a map of column values, each containing a reference key and an aggregation array with all matching rows.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ColumnAggregatorTask`
* **Blocking task**

Accepted inputs
---------------

`array`: each input must be an associative array containing (at least) the configured column keys

Possible outputs
----------------

`array`: associative array keyed by column values, each entry containing:
* `<reference_key>`: the column value
* `<aggregation_key>`: array of all input rows matching this column

Options
-------

| Code              | Type      | Required | Default    | Description                                                                      |
|-------------------|-----------|:--------:|------------|----------------------------------------------------------------------------------|
| `columns`         | `array`   | **X**    |            | List of column keys to aggregate on                                              |
| `reference_key`   | `string`  |          | `column`   | Key name for the column reference in the output                                  |
| `aggregation_key` | `string`  |          | `values`   | Key name for the aggregated rows array in the output                             |
| `condition`       | `array`   |          |            | Condition (via [ConditionTrait](../traits/condition_trait.md)) to filter inputs   |
| `ignore_missing`  | `boolean` |          | `false`    | If `true`, missing columns produce a warning instead of an exception             |

Example
-------

```yaml
# Task configuration level
aggregate_columns:
  service: '@CleverAge\ProcessBundle\Task\ColumnAggregatorTask'
  options:
    columns: [status, category]
    reference_key: group
    aggregation_key: items
```
