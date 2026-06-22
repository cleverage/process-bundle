ArrayMergeTask
==============

Merge every input array into a single result using a configurable merge function. The result is only produced once
all preceding tasks are resolved.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\ArrayMergeTask`
* **Blocking task**

Accepted inputs
---------------

`array`: each input must be an array

Possible outputs
----------------

`array`: the merged result of all accumulated input arrays

Options
-------

| Code             | Type     | Required | Default        | Description                                                                                                         |
|------------------|----------|:--------:|----------------|---------------------------------------------------------------------------------------------------------------------|
| `merge_function` | `string` |          | `array_merge`  | PHP merge function to use. Allowed: `array_merge`, `array_merge_recursive`, `array_replace`, `array_replace_recursive` |

Example
-------

```yaml
# Task configuration level
merge:
  service: '@CleverAge\ProcessBundle\Task\ArrayMergeTask'
  options:
    merge_function: array_replace_recursive
```
