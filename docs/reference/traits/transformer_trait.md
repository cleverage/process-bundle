TransformerTrait
================

Allow to hold a list of sub-transformers, configure their options at initialization, and apply them in sequence.

## Reference

* Namespace: `CleverAge\ProcessBundle\Transformer\TransformerTrait`
* Options algorithm:
  - the option (`transformers` by default) is an ordered list of `transformer code => transformer options`
  - options must be an `array` or `null` (`~`); anything else throws an `InvalidArgumentException`
  - options are resolved once, when the parent options are resolved, with the `configureOptions` method of the
    matching transformer; a transformer that is not configurable must not receive options
  - an unknown transformer code throws a `MissingTransformerException`
  - at runtime, transformers are applied in the declared order, each one receiving the output of the previous one
  - any error thrown by a transformer is wrapped in a `TransformerException` ("Transformation '<code>' have failed:
    <original message>"), the original exception being available as previous exception
  - as YAML keys must be unique, a suffix starting with `#` can be added to the code to use the same transformer
    several times. The convention is `#` followed by digits; the part before the first `#` is used as the transformer
    code if it is registered. Example:

```yaml
transformers:
  callback#1:
    callback: array_filter
  callback#2:
    callback: array_reverse
```

## Usage

* Set the `$transformerRegistry` property with the `TransformerRegistry` service (e.g. in the constructor)
* Call `TransformerTrait::configureTransformersOptions` with your own `OptionsResolver`. You can change `$optionName`
  if you want a custom option name
* Call `TransformerTrait::applyTransformers` with the resolved transformer option (i.e. `$options['transformers']`)
  and the value you want to transform

## Implementors

* [TransformerTask](../tasks/transformer_task.md): `transformers` option
* [ArrayMapTransformer](../transformers/array_map_transformer.md): `transformers` option, applied on each item
* [CachedTransformer](../transformers/cached_transformer.md): `transformers` and `key_transformers` options
* [MappingTransformer](../transformers/mapping_transformer.md): `transformers` option of each mapped property
* [RulesTransformer](../transformers/rules_transformer.md): `transformers` option of each rule
* [GenericTransformer](../transformers/generic_transformer.md), see
  [Generic transformers definition](../03-generic_transformers_definition.md)
