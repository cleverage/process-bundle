UnsetTransformer
================

Remove a key from the input array, optionally only when a condition is met.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\UnsetTransformer`
* **Transformer code**: `unset`

Accepted inputs
---------------

`array` containing the `property` key. An `UnexpectedValueException` is thrown if the input is not an array, or if the
key does not exist (even when the condition is not met).

Possible outputs
----------------

`array`: the input, without the `property` key if the condition is met.

Options
-------

| Code        | Type     | Required | Default | Description                                                                                                                             |
|-------------|----------|:--------:|---------|-----------------------------------------------------------------------------------------------------------------------------------------|
| `property`  | `string` |  **X**   |         | Array key to remove (a plain key, not a property path)                                                                                  |
| `condition` | `array`  |          | `[]`    | Conditions checked against the input before removing the key, see [ConditionTrait](../traits/condition_trait.md). Always met when empty |

Examples
--------

* Unconditionally remove a key

```yaml
# Transformer options level
unset:
  property: internal_id
```

* Remove the key only if another value matches

```yaml
# Transformer options level
unset:
  property: debug_info
  condition:
    match:
      '[environment]': production
```
