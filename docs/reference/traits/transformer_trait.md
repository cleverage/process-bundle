TransformerTrait
================

Allow to hold a list of sub-transformers, configure their options at initialization, and apply them in sequence.

## Reference

* Namespace: `CleverAge\ProcessBundle\Transformer\TransformerTrait`
* Options algorithm:
  - the option (`transformers` by default) is either an ordered map of `transformer code => transformer options`, or a
    list (see [List syntax](#list-syntax)) whose items are a transformer code without options (`- trim`) or a single
    `transformer code: transformer options` map (`- callback: { callback: array_filter }`)
  - options must be an `array` or `null` (`~`); anything else throws an `InvalidArgumentException`
  - options are resolved once, when the parent options are resolved, with the `configureOptions` method of the
    matching transformer; a transformer that is not configurable must not receive options
  - an unknown transformer code throws a `MissingTransformerException`
  - at runtime, transformers are applied in the declared order, each one receiving the output of the previous one
  - any error thrown by a transformer is wrapped in a `TransformerException` ("Transformation '<code>' have failed:
    <original message>"), the original exception being available as previous exception. With the list syntax, the
    code is followed by `#` and the position of the transformer in the list, starting at 0 (`callback#1`)
  - as YAML keys must be unique, a suffix starting with `#` can be added to the code to use the same transformer
    several times. Any non-empty suffix is accepted: digits (`callback#1`) or a name describing the step
    (`callback#reverse`). The part before the first `#` is used as the transformer code if it is registered (otherwise
    the whole code is looked up, and a `MissingTransformerException` is thrown if it is unknown). Example:

```yaml
transformers:
  callback#1:
    callback: array_filter
  callback#reverse:
    callback: array_reverse
```

### List syntax

The list syntax allows to use the same transformer several times without suffix. Each item is either a transformer
code (for a transformer without options) or a map with a single `transformer code: transformer options` entry; any
other item throws an `InvalidArgumentException`. Example:

```yaml
transformers:
  - trim
  - callback:
      callback: array_filter
  - callback:
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
