InputAggregatorTask
===================

**Deprecated**: this class is marked `@deprecated` (too error-prone, should be refactored as a blocking task).

Accumulates inputs coming from several parent tasks. The task is skipped until an input has been received from every
parent declared in `input_codes`; it then outputs an array of all received inputs, indexed by their destination key,
and clears its buffer (except for the keys listed in `keep_inputs`).

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\InputAggregatorTask`

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
  service: '@CleverAge\ProcessBundle\Task\DummyTask'
  outputs: [branch_a, branch_b]
branch_a:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output: A
  outputs: [aggregate]
branch_b:
  service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
  options:
    output: B
  outputs: [aggregate]
aggregate:
  service: '@CleverAge\ProcessBundle\Task\InputAggregatorTask'
  options:
    input_codes:
      branch_a: a
      branch_b: b
```

Notes
-----

In iterable processes, inputs of different iterations may get mixed (for instance when a parent task skips an item
because of an error), even with `clean_input_on_override: true`. Its usage is **strongly discouraged** outside of
non-iterable processes; prefer a blocking task such as [ArrayMergeTask](array_merge_task.md) or
[AggregateIterableTask](aggregate_iterable_task.md) when possible.
