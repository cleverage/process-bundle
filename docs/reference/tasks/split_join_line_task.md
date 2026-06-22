SplitJoinLineTask
=================

Split a single line (array) into multiple lines based on multiple columns and a split character. Each value from the
split columns produces a new output line, with the split value stored in a configurable join column.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\SplitJoinLineTask`
* **Iterable task**

Accepted inputs
---------------

`array`: a single row with keys, including the columns to split

Possible outputs
----------------

`array`: multiple rows, one for each split value. Each row contains all original columns (except the split columns)
plus a `join_column` containing the individual split value.

Options
-------

| Code              | Type     | Required | Default | Description                                      |
|-------------------|----------|:--------:|---------|--------------------------------------------------|
| `split_columns`   | `array`  | **X**    |         | List of column keys whose values will be split   |
| `join_column`     | `string` | **X**    |         | Name of the output column containing split values|
| `split_character` | `string` |          | `,`     | Character used to split column values             |

Example
-------

* Given input: `{category: "A,B,C", tag: "x,y", name: "Item1"}`

```yaml
# Task configuration level
split_line:
  service: '@CleverAge\ProcessBundle\Task\SplitJoinLineTask'
  options:
    split_columns: [category, tag]
    join_column: value
    split_character: ','
```

This will produce 5 iterations:
- `{name: "Item1", value: "A"}`
- `{name: "Item1", value: "B"}`
- `{name: "Item1", value: "C"}`
- `{name: "Item1", value: "x"}`
- `{name: "Item1", value: "y"}`
