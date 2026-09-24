GroupByAggregateIterableTask
============================

Aggregates inputs in an associative array, indexed by a key built from configurable properties of each input, and
outputs it once all previous tasks are resolved. An input with the same key as a previous one replaces it, so this
task can be used to remove duplicates.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\GroupByAggregateIterableTask`
* **Blocking task**

Accepted inputs
---------------

`array` or `object`: values are read with the Symfony PropertyAccessor. If a property cannot be read, the exception
is handled according to the task `error_strategy`.

Possible outputs
----------------

`array`: inputs indexed by the values of `group_by_accessors` joined with `-` (last input wins for a given key). The
task is skipped if no input was received.

Options
-------

| Code                 | Type    | Required | Default | Description                                           |
|----------------------|---------|:--------:|---------|-------------------------------------------------------|
| `group_by_accessors` | `array` |  **X**   |         | List of property paths used to build the grouping key |

Examples
--------

* Deduplicate items on `type` and `code`

```yaml
# Task configuration level
deduplicate:
  service: '@CleverAge\ProcessBundle\Task\GroupByAggregateIterableTask'
  options:
    group_by_accessors:
      - '[type]'
      - '[code]'
```

With inputs `{type: A, code: 1, v: x}` and `{type: A, code: 1, v: y}`, the output is `{A-1: {type: A, code: 1, v: y}}`.
