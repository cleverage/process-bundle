RowAggregatorTask
=================

Groups input rows sharing the same value for the `aggregate_by` column. Each group is made of the first received row
(without the `aggregate_columns`), plus a sub-array under `aggregation_key` listing the `aggregate_columns` values of
every row of the group. The groups are output once all previous tasks are resolved.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\RowAggregatorTask`
* **Blocking task**

Accepted inputs
---------------

`array`: an associative array containing the `aggregate_by` key and all the `aggregate_columns` keys, otherwise an
`InvalidProcessConfigurationException` is thrown

Possible outputs
----------------

`array`: list of groups (indexed numerically, in order of first appearance)

Options
-------

| Code                | Type     | Required | Default | Description                                                  |
|---------------------|----------|:--------:|---------|--------------------------------------------------------------|
| `aggregate_by`      | `string` |  **X**   |         | Column used to group rows together                           |
| `aggregate_columns` | `array`  |  **X**   |         | List of columns to collect into the aggregation sub-array    |
| `aggregation_key`   | `string` |  **X**   |         | Key of the sub-array containing the aggregated column values |

Examples
--------

* Group order lines by order

```yaml
# Task configuration level
aggregate_rows:
  service: '@CleverAge\ProcessBundle\Task\RowAggregatorTask'
  options:
    aggregate_by: order_id
    aggregate_columns: [product, qty]
    aggregation_key: lines
```

With inputs `{order_id: 1, customer: X, product: A, qty: 2}`, `{order_id: 1, customer: X, product: B, qty: 3}` and
`{order_id: 2, customer: Y, product: C, qty: 1}`, the output is:

```yaml
- { order_id: 1, customer: X, lines: [{ product: A, qty: 2 }, { product: B, qty: 3 }] }
- { order_id: 2, customer: Y, lines: [{ product: C, qty: 1 }] }
```
