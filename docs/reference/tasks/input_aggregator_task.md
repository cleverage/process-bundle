InputAggregatorTask
===================

Joins the inputs coming from several parent tasks. The task is skipped until an input has been received from every
parent declared in `input_codes`; it then outputs an array of all received inputs, indexed by their destination key,
and clears its buffer (except for the keys listed in `keep_inputs`).

Unlike a blocking task (e.g. [AggregateIterableTask](aggregate_iterable_task.md)), which waits for all the items and
outputs them once, it outputs once **per item** in an iterable process.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\InputAggregatorTask`
* **Code**: `input_aggregator` (`task: input_aggregator`)

Accepted inputs
---------------

`any`: the parent task (previous task) code must be declared in `input_codes`, otherwise an
`\UnexpectedValueException` is thrown. The task cannot be used without a previous task (e.g. as an entry point).

Possible outputs
----------------

`array`: destination key (from `input_codes`) => input received from the corresponding parent task

Options
-------

| Code                      | Type          | Required | Default | Description                                                                                                                                     |
|---------------------------|---------------|:--------:|---------|-------------------------------------------------------------------------------------------------------------------------------------------------|
| `input_codes`             | `array`       |  **X**   |         | Map of parent task code => destination key in the output                                                                                        |
| `clean_input_on_override` | `bool`        |          | `true`  | When an input is received again for an already filled key: if `true`, all buffered inputs are cleared first; if `false`, an exception is thrown |
| `keep_inputs`             | `array\|null` |          | `null`  | List of destination keys that are kept in the buffer after an output                                                                            |

Examples
--------

* Wait for the results of two branches

```yaml
# Task configuration level
entry:
  task: dummy
  outputs: [branch_a, branch_b]
branch_a:
  task: constant_output
  options:
    output: A
  outputs: [aggregate]
branch_b:
  task: constant_output
  options:
    output: B
  outputs: [aggregate]
aggregate:
  task: input_aggregator
  options:
    input_codes:
      branch_a: a
      branch_b: b
```

* Join two branches for each item of an iterable process, a branch skipping some items

```yaml
# Task configuration level
entry:
  task: constant_iterable_output
  options:
    output: [1, 2, 3]
  outputs: [branch_a, branch_b]
branch_a:
  task: filter
  options:
    not_match: { '': 2 }
  outputs: [aggregate]
branch_b:
  task: transformer
  options:
    transformers:
      sprintf: { format: 'item %s' }
  outputs: [aggregate]
aggregate:
  task: input_aggregator
  options:
    input_codes:
      branch_a: a
      branch_b: b
# Outputs ['a' => 1, 'b' => 'item 1'], then ['a' => 3, 'b' => 'item 3']: the item 2, skipped by branch_a, is dropped
```

Notes
-----

The inputs of an item are told apart by the executions of the ancestors they have in common (their execution count,
`ProcessState::getExecutionCount()`): when an input arrives after a common ancestor has been executed again, the inputs
received from the other branches for a previous item are dropped (except those of `keep_inputs`). An item skipped by a
branch (filter, error with `error_strategy: skip`) is therefore never joined with the next item. Before v5.4, the
inputs of two items could be joined in that case.

Inputs without any common ancestor (e.g. two branches starting with their own root task) are always joined.
