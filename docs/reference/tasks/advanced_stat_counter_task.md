AdvancedStatCounterTask
=======================

Logs performance statistics (`info` level) every N executions: time since the last log, processing rate, number of
processed items and total elapsed time.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Reporting\AdvancedStatCounterTask`

Accepted inputs
---------------

Input is ignored, only the number of executions matters.

Possible outputs
----------------

`null` when statistics are logged, otherwise the task is skipped (nothing is sent to the outputs). It is meant to be
used as the last task of a branch.

The logged message has the following format:

```
Last iteration 00:00:12 ago - 1,50 items/s - 500 items processed in 00:05:33
```

Options
-------

| Code         | Type  | Required | Default | Description                                                                           |
|--------------|-------|:--------:|---------|---------------------------------------------------------------------------------------|
| `num_items`  | `int` |          | `1`     | Number of items represented by one execution (multiplier of the counter)              |
| `skip_first` | `int` |          | `0`     | Number of first executions to ignore (the elapsed time still starts at the first one) |
| `show_every` | `int` |          | `1`     | Log the statistics every N executions (the first counted execution is never logged)   |

Examples
--------

* Log statistics every 100 batches of 50 items

```yaml
# Task configuration level
stats:
  service: '@CleverAge\ProcessBundle\Task\Reporting\AdvancedStatCounterTask'
  options:
    num_items: 50
    show_every: 100
```
