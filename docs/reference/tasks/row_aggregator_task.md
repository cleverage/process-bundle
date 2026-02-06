RowAggregatorTask
=================

Aggregate input rows by a common key. Rows sharing the same value for `aggregate_by` are grouped together, with
specified columns collected into a sub-array under `aggregation_key`.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\RowAggregatorTask`
* **Blocking task**

Accepted inputs
---------------

`array`: each input must be an associative array containing the `aggregate_by` key and all `aggregate_columns` keys

Possible outputs
----------------

`array`: indexed array of grouped rows. Each group contains the base row fields (minus the aggregate columns) plus
a `<aggregation_key>` array containing one entry per aggregated row.

Options
-------

| Code                | Type     | Required | Default | Description                                                        |
|---------------------|----------|:--------:|---------|--------------------------------------------------------------------|
| `aggregate_by`      | `string` | **X**    |         | Column key used to group rows together                             |
| `aggregate_columns` | `array`  | **X**    |         | List of column keys to collect into the aggregation sub-array      |
| `aggregation_key`   | `string` | **X**    |         | Key name for the sub-array containing aggregated column values     |

Example
-------

Given inputs:
```
{order_id: 1, product: "A", qty: 2}
{order_id: 1, product: "B", qty: 3}
{order_id: 2, product: "C", qty: 1}
```

```yaml
# Task configuration level
aggregate_rows:
  service: '@CleverAge\ProcessBundle\Task\RowAggregatorTask'
  options:
    aggregate_by: order_id
    aggregate_columns: [product, qty]
    aggregation_key: lines
```

Output:
```
[
  {order_id: 1, lines: [{product: "A", qty: 2}, {product: "B", qty: 3}]},
  {order_id: 2, lines: [{product: "C", qty: 1}]}
]
```
