DieTask
=========

Stops the process brutally


Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Debug\DieTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

None

Example
----------------

```yaml
clever_age_process:
  configurations:
    project_prefix.die_example:
      tasks:
        die_example:
          service: '@CleverAge\ProcessBundle\Task\Debug\DieTask'
```