ConditionTrait
==============

Provide a configurable set of matching rules, used by tasks and transformers to test an input (e.g. to filter it).

## Reference

* Namespace: `CleverAge\ProcessBundle\Transformer\ConditionTrait`
* Options algorithm:
  - each condition option is a list of `property path => expected value` (or just `property path` for `empty` and
    `not_empty`, the value being ignored)
  - the property path is read on the input with the Symfony
    [PropertyAccessor](https://symfony.com/doc/current/components/property_access.html): use `[key]` for arrays,
    `property` or `a.b` for objects. An unreadable path gives `null`, without error. The empty path `''` targets the
    whole input
  - all conditions must match (logical **AND**, there is no **OR**): the checks are run in the order `match`, `empty`,
    `match_regexp`, `not_match`, `not_empty`, `not_match_regexp`, and stop at the first failure
  - with no condition at all, the input always matches

## Options

| Code               | Type    | Required | Default | Description                                                                                                         |
|--------------------|---------|:--------:|---------|---------------------------------------------------------------------------------------------------------------------|
| `match`            | `array` |          | `[]`    | `path => value`: the value at `path` must be strictly equal (`===`) to `value`                                      |
| `not_match`        | `array` |          | `[]`    | `path => value`: the value at `path` must not be strictly equal (`!==`) to `value`                                  |
| `empty`            | `array` |          | `[]`    | `path => ~`: the value at `path` must be empty (PHP `empty()`: `null`, `''`, `'0'`, `0`, `false`, `[]`, or missing) |
| `not_empty`        | `array` |          | `[]`    | `path => ~`: the value at `path` must not be empty                                                                  |
| `match_regexp`     | `array` |          | `[]`    | `path => pattern`: the value at `path`, cast to string, must match the regular expression                           |
| `not_match_regexp` | `array` |          | `[]`    | `path => pattern`: the value at `path`, cast to string, must not match the regular expression                       |

An invalid regular expression makes both `match_regexp` and `not_match_regexp` fail.

## Usage

* Call `ConditionTrait::configureConditionOptions` to add the condition options at the root of your `OptionsResolver`,
  or `ConditionTrait::configureWrappedConditionOptions` to add them under a single option (e.g. `condition`)
* Set the `$accessor` property with a `PropertyAccessorInterface` (e.g. in the constructor)
* Call `ConditionTrait::checkCondition` with the input and the resolved conditions; it returns `true` if all
  conditions match

The input must be an `array` or an `object` as soon as a condition is defined (a scalar input raises a `TypeError`).

## Examples

* Strict equality: the `[status]` key must be `active` (string) and `[stock]` must not be `0` (integer)

```yaml
match:
  '[status]': active
not_match:
  '[stock]': 0
```

* Emptiness: `[email]` must be filled and `[deleted_at]` must be empty or missing

```yaml
not_empty:
  '[email]': ~
empty:
  '[deleted_at]': ~
```

* Regular expressions, on an object input

```yaml
match_regexp:
  sku: '/^[A-Z]{3}-\d+$/'
not_match_regexp:
  customer.email: '/@example\.com$/'
```

* Wrapped in a `condition` option (e.g. [ArrayFilterTransformer](../transformers/array_filter_transformer.md))

```yaml
# Transformer options level
array_filter:
  condition:
    match:
      '[type]': product
```

## Implementors

* [FilterTask](../tasks/filter_task.md): condition options at the root of the task options
* [ColumnAggregatorTask](../tasks/column_aggregator_task.md): `condition` option, checked against
  `{input_column_value: <column value>, input: <input>}` (so use paths like `[input_column_value]` or `[input][key]`)
* [ArrayFilterTransformer](../transformers/array_filter_transformer.md): `condition` option, checked against each item
* [UnsetTransformer](../transformers/unset_transformer.md): `condition` option, checked against the input array
