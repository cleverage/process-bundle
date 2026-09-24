ArrayFirstTransformer
=====================

Return the first element of an array, using `reset()`.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayFirstTransformer`
* **Transformer code**: `array_first`

Accepted inputs
---------------

`array`. With the default options, any non-iterable value is accepted and returned unchanged.

Possible outputs
----------------

* `any`: the first element of the array
* `false` if the array is empty
* the input value itself if it is not iterable and `allow_not_iterable` is `false`

Options
-------

| Code                 | Type   | Required | Default | Description                                                                      |
|----------------------|--------|:--------:|---------|----------------------------------------------------------------------------------|
| `allow_not_iterable` | `bool` |          | `false` | When `false`, a non-iterable input is returned unchanged (see [Notes](#notes))   |

Examples
--------

* `['foo', 'bar', 'baz']` becomes `'foo'`

```yaml
# Transformer options level
array_first: ~
```

Notes
-----

The `allow_not_iterable` option behaves counter-intuitively: when set to `true`, the non-iterable check is skipped and
`reset()` is called on the raw value, which throws a `\TypeError` for scalar or `null` inputs (objects are accepted by
`reset()`, which then returns their first public property). Keep the default value.
