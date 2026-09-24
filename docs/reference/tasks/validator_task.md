ValidatorTask
=============

Validates the input with the Symfony Validator component and outputs it unchanged when it is valid.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Validation\ValidatorTask`

Accepted inputs
---------------

`mixed`: any value to validate (object, array, scalar).

Possible outputs
----------------

`mixed`: the input, unchanged, when there is no violation.

When violations are found, each one is logged (if `log_errors` is enabled) with the `property`, `violation_code` and
`invalid_value` in the log context, then:

* if `error_output_violations` is `true`: the `ConstraintViolationListInterface` is sent to the error output and the
  task is skipped (nothing is sent to the outputs)
* otherwise: an `\UnexpectedValueException` is thrown (`N constraint violations detected on validation`), handled
  according to the task `error_strategy`

Options
-------

| Code                      | Type           | Required | Default    | Description                                                                                                                              |
|---------------------------|----------------|:--------:|------------|------------------------------------------------------------------------------------------------------------------------------------------|
| `log_errors`              | `string\|bool` |          | `critical` | PSR log level (`Psr\Log\LogLevel` values) used to log each violation. `true` means `critical`, `false` disables logging                  |
| `groups`                  | `array\|null`  |          | `null`     | Validation groups, passed to `ValidatorInterface::validate()`                                                                            |
| `constraints`             | `array\|null`  |          | `null`     | Constraints to validate against, using the same syntax as Symfony's YAML validation mapping. If `null`, the input class metadata is used |
| `error_output_violations` | `bool`         |          | `false`    | Send the violations to the error output and skip the task instead of throwing an exception                                               |

Constraints are built by `CleverAge\ProcessBundle\Validator\ConstraintLoader`: each constraint is a single-key map
`ConstraintName: options`. The name is either a short name of a Symfony built-in constraint (`NotBlank`, `Collection`,
...) or a FQCN. Options can contain nested constraints.

Examples
--------

* Validate an entity with its own metadata (attributes, YAML mapping...) for the `import` group

```yaml
# Task configuration level
validate:
  service: '@CleverAge\ProcessBundle\Task\Validation\ValidatorTask'
  options:
    groups: [import]
  outputs: [save]
```

* Validate an array with inline constraints and forward violations to an error branch

```yaml
# Task configuration level
validate:
  service: '@CleverAge\ProcessBundle\Task\Validation\ValidatorTask'
  options:
    log_errors: warning
    error_output_violations: true
    constraints:
      - Collection:
          allowExtraFields: true
          fields:
            sku:
              - NotBlank: ~
            price:
              - Type: numeric
              - PositiveOrZero: ~
  outputs: [save]
  error_outputs: [log_violations]
```
