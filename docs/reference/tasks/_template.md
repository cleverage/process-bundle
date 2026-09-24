TaskName
========

_Describe the main goal and use cases of the task._

Task reference
--------------

* **Service**: `Fully\Qualified\ClassName`
* **Iterable task** _(only if it implements `IterableTaskInterface`)_
* **Blocking task** _(only if it implements `BlockingTaskInterface`)_
* **Flushable task** _(only if it implements `FlushableTaskInterface`)_

Accepted inputs
---------------

_Description of allowed types, or "Input is ignored"._

Possible outputs
----------------

_Description of possible types._

Options
-------

| Code   | Type   | Required | Default         | Description   |
|--------|--------|:--------:|-----------------|---------------|
| `code` | `type` |  **X**   | `default value` | _description_ |

_If the task has no option, replace the table with "This task has no option."._

Examples
--------

* Example 1
  - details

```yaml
# Task configuration level
code:
  service: '@Fully\Qualified\ClassName'
  options:
    a: 1
    b: 2
  outputs: [next_task]
```
