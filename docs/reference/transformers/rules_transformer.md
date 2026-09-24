RulesTransformer
================

Use an ordered set of rules to conditionally transform a value. Behaves like an `if / elseif / else` block: the first
rule whose condition matches is applied, and the input is returned unchanged if no rule matches.

Conditions are [ExpressionLanguage](https://symfony.com/doc/current/components/expression_language.html) expressions.
By default, the input is available as the `value` variable. With `use_value_as_variables: true`, the input (which
must then be an array) is used as the set of variables; `expression_variables` must then list these variable names.
`expression_variables` can also be set to `null` to disable parsing at initialization (more flexible, but slower).

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\RulesTransformer`
* **Transformer code**: `rules`

Accepted inputs
---------------

`any`, or an `array` of `variable name => value` when `use_value_as_variables` is `true`.

Possible outputs
----------------

`any`: the result of the matching rule (`null`, a constant, or the result of its transformers), or the input itself
if no rule matches.

Options
-------

| Code                     | Type          | Required | Default   | Description                                                                                            |
|--------------------------|---------------|:--------:|-----------|--------------------------------------------------------------------------------------------------------|
| `rules_set`              | `array`       |  **X**   |           | Ordered list of rules, see below                                                                       |
| `use_value_as_variables` | `bool`        |          | `false`   | Use the input array as the expression variables, instead of a single `value` variable                  |
| `expression_variables`   | `array\|null` |          | `[value]` | Variable names used to parse conditions when options are resolved. `null` defers parsing to evaluation |

Each rule of `rules_set` has the following options:

| Code           | Type           | Required | Default | Description                                                                                                                           |
|----------------|----------------|:--------:|---------|---------------------------------------------------------------------------------------------------------------------------------------|
| `condition`    | `string\|null` |          | `null`  | Expression; the rule matches if it is truthy                                                                                          |
| `default`      | `bool`         |          | `false` | Mark the rule as the default one (always matches). It cannot have a `condition`, no conditional rule may follow it, only one allowed  |
| `set_null`     | `bool`         |          | `false` | If `true`, return `null` (takes precedence on `constant` and `transformers`)                                                          |
| `constant`     | `any`          |          | `null`  | If not `null`, return this value (takes precedence on `transformers`)                                                                 |
| `transformers` | `array`        |          | `[]`    | Transformers applied on the input, see [TransformerTrait](../traits/transformer_trait.md). With no transformer, the input is returned |

A rule without `condition` and without `default: true` never matches.

Examples
--------

* Rules on the `value` variable, with a default rule

```yaml
# Transformer options level
rules:
  rules_set:
    - condition: 'value["order"]["origin"] === "marketplace"'
      transformers:
        property_accessor:
          property_path: '[customer][id]'
    - condition: 'value["order"]["origin"] === "e-commerce"'
      constant: value1234
    - default: true
      set_null: true
```

* Same rules, using the input keys as variables (transformers still receive the whole input)

```yaml
# Transformer options level
rules:
  use_value_as_variables: true
  expression_variables: [order, customer]
  rules_set:
    - condition: 'order["origin"] === "marketplace"'
      transformers:
        property_accessor:
          property_path: '[customer][id]'
    - condition: 'order["origin"] === "e-commerce"'
      constant: variable1234
    - default: true
      set_null: true
```

Notes
-----

Conditions are parsed with the bundle's `cleverage_process.expression_language` service, which also exposes the PHP
`preg_match` function.
