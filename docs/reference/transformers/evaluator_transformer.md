EvaluatorTransformer
====================

Evaluate a Symfony [ExpressionLanguage](https://symfony.com/doc/current/components/expression_language.html)
expression, using the input array as the expression variables.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\EvaluatorTransformer`
* **Transformer code**: `evaluator`

Accepted inputs
---------------

`array`: variable name => value, injected in the expression. Any other type raises a `TypeError`.

Possible outputs
----------------

`any`: the result of the expression.

Options
-------

| Code         | Type                       | Required | Default | Description                                                                                                                    |
|--------------|----------------------------|:--------:|---------|--------------------------------------------------------------------------------------------------------------------------------|
| `expression` | `string\|ParsedExpression` |  **X**   |         | The expression to evaluate                                                                                                     |
| `variables`  | `array\|null`              |          | `null`  | List of variable names. If set, the expression is parsed once when options are resolved; if `null`, it is parsed on evaluation |

Examples
--------

```yaml
# Transformer options level
evaluator:
  expression: 'price * quantity'
  variables: [price, quantity]
```

* Using a mapping to build the variables

```yaml
# Transformer mapping level
total:
  code:
    price: '[unit_price]'
    quantity: '[qty]'
  transformers:
    evaluator:
      expression: 'price * quantity'
```

Notes
-----

This transformer uses its own `ExpressionLanguage` instance, not the bundle's `cleverage_process.expression_language`
service: the extra functions registered on that service (e.g. `preg_match`) are not available here, unlike in
[RulesTransformer](rules_transformer.md) and [ExpressionLanguageMapTransformer](expression_language_map_transformer.md).
