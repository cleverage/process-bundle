RulesTransformer
================

Uses a set of rules to apply some set of transformers on a value. Basically behaves like a `if/elseif/else` block.

By default a rule uses a variable named `value` containing anything you passed in input (`array`, `string`, ...). But this 
can be overridden using options `use_value_as_variables` as `true` and setting `expression_variables` to a static list of
input variables.

Note that `expression_variables` can also be set to `null` for more flexibility, but this disable initial parsing and decrease
performances.

See [The ExpressionLanguage Component Reference](https://symfony.com/doc/current/components/expression_language.html) for
more information.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\RulesTransformer`
* **Transformer code**: `rules`

Accepted inputs
---------------

`any` or an `array` of `variable code => value` injectable into an expression

Possible outputs
----------------

`any` resulting from a transformation set.

Without any matching rules, the value itself is returned. 

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `rules_set` | `array` | **X** | | Ordered list of rules, see bellow for details |
| `use_value_as_variables` | `bool` | | `false` | Use given value as an array of variable to inject in expression |
| `expression_variables` | `array` or `null` | | `[value]` | Name of variables injected in the expression at initialization time |

Foreach rule there is the following options.

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `condition` | `string` or `null` | | `null` | An expression used to match a value |
| `default` | `bool` | | `false` | Mark this rule as a default rule. The given rule must be the last, cannot have a condition, and there cannot have 2 default in the same time |
| `transformers` | `array` | | `[]` | List of transformer code => transformer options for subsequent transformations |
| `constant` | `any` | | `null` | If not `null`, given value will be directly output (takes precedence on `transformers`) |
| `set_null` | `bool` | | `false` | If `true`, `null` will be directly output (takes precedence on `constant`) |

Examples
--------

* Simple rules with default value
  - input value is an array containing an `order` object and a `customer` object
  - output will be either a value from customer, or a numeric constant, or null

```yaml
# Transformer options level
rules:
    rules_set:
        -   condition: 'value["order"].origin === "marketplace"'
            transformers:
                property_accessor:
                    property_path: '[customer].id'
        -   condition: 'value["order"].origin === "e-commerce"'
            constant: 1234
        -   default: true
            set_null: true
```

* Use value as variables
  - same example as above
  - can be useful for more verbose expression
  - transformers still get the input as the initial array 

```yaml
# Transformer options level
rules:
    use_value_as_variables: true
    expression_variables: [order, customer]
    rules_set:
        -   condition: 'order.origin === "marketplace"'
            transformers:
                property_accessor:
                    property_path: '[customer].id'
        -   condition: 'order.origin === "e-commerce"'
            constant: 1234
        -   default: true
            set_null: true
```
