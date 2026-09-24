GenericTransformer
==================

Class behind the transformers declared in the `clever_age_process.generic_transformers` configuration (see
[Generic transformers definition](../03-generic_transformers_definition.md)). It is not a service by itself: one
instance is created per declared generic transformer, and it simply applies a preconfigured chain of transformers,
optionally parametrized by `contextual_options`.

For each entry of `generic_transformers`, the bundle extension (`CleverAgeProcessExtension`) registers a private
service `CleverAge\ProcessBundle\Transformer\GenericTransformer\<code>`, tagged `cleverage.transformer`, and calls
`initialize(<code>, <configuration>)` on it. The instance is then added to the transformer registry like any other
transformer, under the configured code (which must not collide with an existing transformer code).

When the generic transformer is used, its options are the declared contextual options. The `{{ option_code }}`
placeholders in the preconfigured `transformers` (keys and values, recursively) are replaced by the option values,
then the resulting transformer chain is resolved and applied, as with [TransformerTrait](../traits/transformer_trait.md).
A placeholder that is the whole string is replaced by the raw value (keeping its type, e.g. `int` or `null`); otherwise
the value is inserted in the string.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\GenericTransformer\<code>` (one service per generic transformer)
* **Transformer code**: `<code>`: the key used under `clever_age_process.generic_transformers`

Accepted inputs
---------------

`any`: depends on the preconfigured transformers.

Possible outputs
----------------

`any`: result of the preconfigured transformer chain.

Options
-------

The definition (`clever_age_process.generic_transformers.<code>`) accepts:

| Code                 | Type    | Required | Default | Description                                                                                                             |
|----------------------|---------|:--------:|---------|-------------------------------------------------------------------------------------------------------------------------|
| `contextual_options` | `array` |          | `[]`    | List of `option code => option definition` (see below); each one becomes an option of the transformer                   |
| `transformers`       | `array` |          | `[]`    | Transformer chain, see [TransformerTrait](../traits/transformer_trait.md). May contain `{{ option_code }}` placeholders |

Each contextual option definition (`~` is allowed to use all defaults) accepts:

| Code              | Type   | Required | Default | Description                                                                      |
|-------------------|--------|:--------:|---------|----------------------------------------------------------------------------------|
| `required`        | `bool` |          | `true`  | The option must be provided when using the transformer (unless it has a default) |
| `default`         | `any`  |          | `null`  | If not `null`, default value of the option                                       |
| `default_is_null` | `bool` |          | `false` | Use `null` as default value (not possible with `default`)                        |

When using the generic transformer, only its contextual options are accepted; passing `transformers` throws an
`InvalidArgumentException`.

Examples
--------

* Definition without option, and usage

```yaml
# Bundle configuration level (config/packages/*.yaml)
clever_age_process:
  generic_transformers:
    uppercase:
      transformers:
        callback:
          callback: strtoupper
```

```yaml
# Transformer options level
uppercase: ~
```

* Definition with contextual options, and usage: `substr($value, 2, null)`

```yaml
# Bundle configuration level (config/packages/*.yaml)
clever_age_process:
  generic_transformers:
    substr:
      contextual_options:
        offset:
          required: true
        length:
          default_is_null: true
      transformers:
        callback:
          callback: substr
          right_parameters: ['{{ offset }}', '{{ length }}']
```

```yaml
# Transformer options level
substr:
  offset: 2
```

Notes
-----

* Contextual options are transformer options, unrelated to the process context (`-c key:value`).
* An option declared with `required: false` and no default is neither required nor defined, so it cannot be used:
  always give such an option a `default` or `default_is_null: true`.
