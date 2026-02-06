ExplodeTransformer
==================

Split a string into an array using a delimiter (PHP's `explode()`). Returns an empty array for `null` or empty
string inputs.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\ExplodeTransformer`
* **Transformer code**: `explode`

Accepted inputs
---------------

`string|null`

Possible outputs
----------------

`array`: the exploded parts

Options
-------

| Code        | Type     | Required | Default | Description                     |
|-------------|----------|:--------:|---------|---------------------------------|
| `delimiter` | `string` | **X**    |         | The delimiter to split on       |

Examples
--------

```yaml
# Transformer options level
explode:
  delimiter: ','
```

Input `"a,b,c"` produces `["a", "b", "c"]`.
