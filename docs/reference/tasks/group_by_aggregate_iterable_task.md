GroupByAggregateIterableTask
============================

Aggregate inputs into an associative array keyed by configurable fields. Later inputs with the same key overwrite
earlier ones, which effectively deduplicates the data.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\GroupByAggregateIterableTask`
* **Blocking task**

Accepted inputs
---------------

`array` or `object`: data accessible via Symfony's PropertyAccessor

Possible outputs
----------------

`array`: associative array keyed by the concatenated group-by values (joined with `-`), or skipped if empty

Options
-------

| Code                 | Type    | Required | Default | Description                                                                      |
|----------------------|---------|:--------:|---------|----------------------------------------------------------------------------------|
| `group_by_accessors` | `array` | **X**    |         | List of property paths used to build the grouping key                            |

Example
-------

```yaml
# Task configuration level
deduplicate:
  service: '@CleverAge\ProcessBundle\Task\GroupByAggregateIterableTask'
  options:
    group_by_accessors:
      - '[type]'
      - '[code]'
```

With inputs `{type: A, code: 1, ...}` and `{type: A, code: 1, ...}`, the second input overwrites the first,
producing a single entry keyed `A-1`.
