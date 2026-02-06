AdvancedStatCounterTask
=======================

Log performance statistics at regular intervals during iteration. Displays time between iterations, items per
second rate, total items processed, and total elapsed time.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Reporting\AdvancedStatCounterTask`

Accepted inputs
---------------

`any`: input is not used, only the iteration count matters

Possible outputs
----------------

No output is set (the task skips most iterations). Statistics are logged via the logger.

Options
-------

| Code         | Type      | Required | Default | Description                                                       |
|--------------|-----------|:--------:|---------|-------------------------------------------------------------------|
| `num_items`  | `integer` |          | `1`     | Number of logical items per iteration (multiplier for rate calc)  |
| `skip_first` | `integer` |          | `0`     | Number of initial iterations to skip before tracking              |
| `show_every` | `integer` |          | `1`     | Display statistics every N iterations                             |

Example
-------

```yaml
# Task configuration level
stats:
  service: '@CleverAge\ProcessBundle\Task\Reporting\AdvancedStatCounterTask'
  options:
    show_every: 100
    num_items: 1
```

Output in logs (every 100 iterations):
```
Last iteration 00:00:12 ago - 1,50 items/s - 500 items processed in 00:05:33
```
