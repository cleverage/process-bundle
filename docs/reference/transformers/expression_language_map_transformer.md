ExpressionLanguageMapTransformer
================================

Parse an input using the ExpressionLanguage component and return a specific value based on the first matching
condition. Behaves like a `switch/case` using expressions.

See [The ExpressionLanguage Component](https://symfony.com/doc/current/components/expression_language.html) for
syntax reference.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\ExpressionLanguageMapTransformer`
* **Transformer code**: `expression_language_map`

Accepted inputs
---------------

`any`: passed as the `data` variable in expressions

Possible outputs
----------------

`any`: the evaluated `output` expression of the first matching `condition`

Options
-------

| Code             | Type      | Required | Default | Description                                                                     |
|------------------|-----------|:--------:|---------|---------------------------------------------------------------------------------|
| `map`            | `array`   | **X**    |         | Ordered list of rules, each with a `condition` and an `output` expression       |
| `ignore_missing` | `boolean` |          | `false` | If `true`, return `null` when no condition matches                              |
| `keep_missing`   | `boolean` |          | `false` | If `true`, return the original value when no condition matches                  |

Each entry in `map` must have:

| Code        | Type     | Required | Description                                           |
|-------------|----------|:--------:|-------------------------------------------------------|
| `condition` | `string` | **X**    | ExpressionLanguage expression evaluated with `data`   |
| `output`    | `string` | **X**    | ExpressionLanguage expression evaluated on match      |

Examples
--------

```yaml
# Transformer options level
expression_language_map:
  map:
    - condition: 'data > 100'
      output: '"high"'
    - condition: 'data > 50'
      output: '"medium"'
    - condition: 'data >= 0'
      output: '"low"'
  ignore_missing: true
```
