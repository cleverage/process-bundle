ArrayFirstTransformer
=====================

Return the first element of an array, or of any iterable.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Array\ArrayFirstTransformer`
* **Transformer code**: `array_first`

Accepted inputs
---------------

`iterable` (`array`, `\Traversable`). Any other value throws an `\UnexpectedValueException`, unless
`allow_not_iterable` is `true`.

Possible outputs
----------------

* `any`: the first element of the iterable
* `false` if the iterable is empty
* the input value itself if it is not iterable and `allow_not_iterable` is `true`

Options
-------

| Code                 | Type   | Required | Default | Description                                                                              |
|----------------------|--------|:--------:|---------|------------------------------------------------------------------------------------------|
| `allow_not_iterable` | `bool` |          | `false` | When `true`, a non-iterable input is returned unchanged instead of throwing an exception |

Examples
--------

* `['foo', 'bar', 'baz']` becomes `'foo'`

```yaml
# Transformer options level
array_first: ~
```

* Get the first element of a list, or keep a single value as is: `['foo', 'bar']` becomes `'foo'`, `'foo'` stays
  `'foo'`

```yaml
# Transformer options level
array_first:
  allow_not_iterable: true
```

Notes
-----

Since v6.0, a non-iterable input throws an exception by default. From v4.0 to v5.x, the option was inverted (a
non-iterable input was returned unchanged by default, and `allow_not_iterable: true` threw a `\TypeError`): to keep the
former default behaviour, set `allow_not_iterable: true`.
