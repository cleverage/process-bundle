UnsetTransformer
================

Remove a property from an input array, optionally based on a condition.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\UnsetTransformer`
* **Transformer code**: `unset`

Accepted inputs
---------------

`array`: the input must be an array containing the property to unset

Possible outputs
----------------

`array`: the input array with the specified property removed (if condition is met)

Options
-------

| Code        | Type     | Required | Default | Description                                                                      |
|-------------|----------|:--------:|---------|----------------------------------------------------------------------------------|
| `property`  | `string` | **X**    |         | Key to remove from the array                                                     |
| `condition` | `array`  |          |         | Condition (via [ConditionTrait](../traits/condition_trait.md)) to check before unsetting |

Examples
--------

* Unconditionally remove a property

```yaml
# Transformer options level
unset:
  property: internal_id
```

* Remove only if condition is met

```yaml
# Transformer options level
unset:
  property: debug_info
  condition:
    match:
      '[environment]': production
```
