ArrayFilterTransformer
======================

Filter the elements of an iterable value, keeping only those matching a set of conditions. It mimics the native
[array_filter](https://www.php.net/manual/en/function.array-filter.php) function behavior: keys are preserved.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayFilterTransformer`
* **Transformer code**: `array_filter`

Accepted inputs
---------------

`iterable` (`array` or `\Traversable`). Any other value throws an `\UnexpectedValueException`.

Possible outputs
----------------

`array`: the elements matching the condition, with their original keys

Options
-------

| Code        | Type    | Required | Default | Description                                                                                   |
|-------------|---------|:--------:|---------|-----------------------------------------------------------------------------------------------|
| `condition` | `array` |          | `[]`    | Conditions each element must match, see [ConditionTrait](../traits/condition_trait.md)        |

The `condition` option accepts the following keys, each one being a map of `property_path: value`. Properties are read
from each element with the Symfony PropertyAccessor (an empty path `''` targets the element itself, which is the way
to filter a list of scalars); an unreadable property, or any non-empty path on a scalar element, is considered `null`.

| Code               | Type    | Required | Default | Description                                                        |
|--------------------|---------|:--------:|---------|--------------------------------------------------------------------|
| `match`            | `array` |          | `[]`    | Property must be strictly equal (`===`) to the value               |
| `not_match`        | `array` |          | `[]`    | Property must not be strictly equal (`!==`) to the value           |
| `empty`            | `array` |          | `[]`    | Property must be empty (`empty()`), the value is ignored           |
| `not_empty`        | `array` |          | `[]`    | Property must not be empty (`empty()`), the value is ignored       |
| `match_regexp`     | `array` |          | `[]`    | Property must match the regular expression given as value          |
| `not_match_regexp` | `array` |          | `[]`    | Property must not match the regular expression given as value      |

With an empty `condition`, every element is kept.

Examples
--------

* Keep only European countries from a list of objects

```yaml
# Transformer options level
array_filter:
  condition:
    match:
      sContinentCode: 'EU'
```

* Keep only array elements with a non-empty `[email]` and a `[sku]` starting with `A`

```yaml
# Transformer options level
array_filter:
  condition:
    not_empty:
      '[email]': ~
    match_regexp:
      '[sku]': '/^A/'
```

* Keep only the `a` values of a list of strings: `[a, b, a]` gives `{0: a, 2: a}`

```yaml
# Transformer options level
array_filter:
  condition:
    match:
      '': a
```
