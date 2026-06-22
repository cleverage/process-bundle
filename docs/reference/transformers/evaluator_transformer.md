EvaluatorTransformer
====================

Evaluate a Symfony ExpressionLanguage expression against the input value. The input is used as the expression
variables.

See [The ExpressionLanguage Component](https://symfony.com/doc/current/components/expression_language.html) for
syntax reference.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\EvaluatorTransformer`
* **Transformer code**: `evaluator`

Accepted inputs
---------------

`array`: an associative array of variables to inject into the expression

Possible outputs
----------------

`any`: the result of the expression evaluation

Options
-------

| Code         | Type              | Required | Default | Description                                                                                     |
|--------------|-------------------|:--------:|---------|-------------------------------------------------------------------------------------------------|
| `expression` | `string`          | **X**    |         | The expression to evaluate                                                                      |
| `variables`  | `array\|null`     |          | `null`  | List of variable names for pre-parsing. If `null`, parsing is deferred (less performant).       |

Examples
--------

```yaml
# Transformer options level
evaluator:
  expression: 'price * quantity'
  variables: [price, quantity]
```
