TransformerTrait
================

Allow to hold a list of sub-transformers and recursively configure their options.

## Reference

* Namespace: `CleverAge\ProcessBundle\Transformer\TransformerTrait`
* Options algorithm: 
  - for each element: the key maps to a transformer code, and the value are resolved by the matching transformer
  - note that the key may be followed by `#` and any digit, to allow multiple transformer of the same type. Example: 
```yaml
transformers:
    transformer_code#1:
        some_options: ~
    transformer_code#2:
        some_options: ~
```

## Usage

* Call `TransformerTrait::configureTransformersOptions` with your own `OptionResolver`. You can change `$optionName` if you want a custom option name
* Call `TransformerTrait::applyTransformers` with the resolved transformer options (i.e. `$options["transformers"]`) and the value you want to pass

## Implementors

* [TransformerTask](../tasks/transformer_task.md)
* [MappingTransformer](../transformers/mapping_transformer.md)
* [RulesTransformer](../transformers/rules_transformer.md)
* [Generic transformers](../03-generic_transformers_definition.md)
