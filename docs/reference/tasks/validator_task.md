ValidatorTask
=============

Validate the input using Symfony's Validator component and pass it to the output. If validation fails, the task
either throws an exception or forwards violations to the error output.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Validation\ValidatorTask`

Accepted inputs
---------------

`any`: data to validate (object, array, scalar)

Possible outputs
----------------

`any`: same as input if validation passes

On error (when `error_output_violations` is `true`):
* error output: `ConstraintViolationListInterface` containing the violations

Options
-------

| Code                       | Type            | Required | Default              | Description                                                                                          |
|----------------------------|-----------------|:--------:|----------------------|------------------------------------------------------------------------------------------------------|
| `log_errors`               | `string\|bool`  |          | `critical`           | PSR log level for violation messages. Set to `false` to disable logging                              |
| `groups`                   | `array\|null`   |          | `null`               | Validation groups to apply                                                                           |
| `constraints`              | `array\|null`   |          | `null`               | Array of constraints definitions (loaded via `ConstraintLoader`). If `null`, uses class annotations  |
| `error_output_violations`  | `bool`          |          | `false`              | If `true`, violations are sent to error output and the task is skipped instead of throwing           |

Example
-------

* Validate input and stop on error

```yaml
# Task configuration level
validate:
  service: '@CleverAge\ProcessBundle\Task\Validation\ValidatorTask'
  options:
    groups: [import]
```

* Validate and forward violations to an error branch

```yaml
# Task configuration level
validate:
  service: '@CleverAge\ProcessBundle\Task\Validation\ValidatorTask'
  options:
    error_output_violations: true
    log_errors: warning
  errors: [handle_violations]
```
