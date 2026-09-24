SplitJoinLineTask
=================

Splits a single line (array) into multiple lines: each configured column is exploded with a split character, and each
resulting value produces a new output line where it is stored in a single "join" column.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\SplitJoinLineTask`
* **Iterable task**

Accepted inputs
---------------

`array`: a line containing all the `split_columns` keys. An `\UnexpectedValueException` is thrown if one is missing.

Possible outputs
----------------

`array`: one line per split value, iterated in the order of `split_columns`. Each line contains all the original columns
except the `split_columns`, plus the `join_column` holding the split value (as a string).

Options
-------

| Code              | Type     | Required | Default | Description                                          |
|-------------------|----------|:--------:|---------|------------------------------------------------------|
| `split_columns`   | `array`  |  **X**   |         | List of the columns whose values will be split       |
| `join_column`     | `string` |  **X**   |         | Name of the output column receiving each split value |
| `split_character` | `string` |          | `,`     | Delimiter used to explode the columns values         |

Examples
--------

* Split two columns into a single `value` column

```yaml
# Task configuration level
split_line:
  service: '@CleverAge\ProcessBundle\Task\SplitJoinLineTask'
  options:
    split_columns: [category, tag]
    join_column: value
    split_character: ','
  outputs: [next_task]
```

With the input `{category: "A,B,C", tag: "x,y", name: "Item1"}`, the task iterates over 5 lines:

- `{name: "Item1", value: "A"}`
- `{name: "Item1", value: "B"}`
- `{name: "Item1", value: "C"}`
- `{name: "Item1", value: "x"}`
- `{name: "Item1", value: "y"}`
