ExpressionLanguageMapTransformer
================================

Return a value computed from the first matching rule of a list of
[ExpressionLanguage](https://symfony.com/doc/current/components/expression_language.html) `condition` / `output`
pairs. Behaves like a `switch/case` based on expressions. The input is available in expressions as the `data`
variable.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\ExpressionLanguageMapTransformer`
* **Transformer code**: `expression_language_map`

Accepted inputs
---------------

`any`: exposed as `data` in expressions.

Possible outputs
----------------

`any`: the evaluated `output` of the first rule whose `condition` is truthy, or the input (`keep_missing`), or `null`
(`ignore_missing`).

Options
-------

| Code             | Type    | Required | Default | Description                                                                                      |
|------------------|---------|:--------:|---------|--------------------------------------------------------------------------------------------------|
| `map`            | `array` |  **X**   |         | Ordered list of rules, see below                                                                 |
| `ignore_missing` | `bool`  |          | `false` | If `true`, return `null` when no rule matches, instead of throwing an `UnexpectedValueException` |
| `keep_missing`   | `bool`  |          | `false` | If `true`, return the input when no rule matches (takes precedence on `ignore_missing`)          |

Each entry of `map` has the following options:

| Code        | Type     | Required | Default | Description                                               |
|-------------|----------|:--------:|---------|-----------------------------------------------------------|
| `condition` | `string` |  **X**   |         | Expression using `data`; the rule matches if it is truthy |
| `output`    | `string` |  **X**   |         | Expression using `data`, evaluated and returned on match  |

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

Notes
-----

* Both `condition` and `output` are expressions: string literals must be quoted inside the expression (`'"high"'`).
* Expressions are parsed once, when options are resolved, with the bundle's `cleverage_process.expression_language`
  service (which also exposes the PHP `preg_match` function).
