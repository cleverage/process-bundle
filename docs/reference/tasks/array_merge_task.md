ArrayMergeTask
==============

Merges every input array into a single result using a configurable PHP merge function, and outputs the result once
all previous tasks are resolved.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ArrayMergeTask`
* **Blocking task**

Accepted inputs
---------------

`array`: any other type throws an `\UnexpectedValueException`

Possible outputs
----------------

`array`: result of `merge_function(previous_result, input)` applied to each input in turn (starting from `[]`)

Options
-------

| Code             | Type     | Required | Default       | Description                                                                                                           |
|------------------|----------|:--------:|---------------|-----------------------------------------------------------------------------------------------------------------------|
| `merge_function` | `string` |          | `array_merge` | PHP function used to merge; one of `array_merge`, `array_merge_recursive`, `array_replace`, `array_replace_recursive` |

Examples
--------

* Merge iterated arrays, later keys overriding earlier ones recursively

```yaml
# Task configuration level
data:
  service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
  options:
    output:
      - { a: 1, b: { c: 2 } }
      - { b: { d: 3 } }
  outputs: [merge]
merge:
  service: '@CleverAge\ProcessBundle\Task\ArrayMergeTask'
  options:
    merge_function: array_replace_recursive
```
