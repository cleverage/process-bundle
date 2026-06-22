StatCounterTask
===============

Count the number of times the task is executed. At the end of the process (finalize), logs the total count.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Reporting\StatCounterTask`
* **Finalizable task**

Accepted inputs
---------------

`any`: input is not used

Possible outputs
----------------

No output is set. The total count is logged during finalization.

Example
-------

```yaml
# Task configuration level
count:
  service: '@CleverAge\ProcessBundle\Task\Reporting\StatCounterTask'
```

At the end of the process, the following will be logged:
```
Processed item count: 1234
```
