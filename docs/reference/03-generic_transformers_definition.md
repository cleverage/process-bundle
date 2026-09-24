Generic transformers definition
===============================

Generic transformers are reusable transformers defined only with configuration: a named chain of existing
transformers, optionally parameterized by *contextual options*. Each one is registered in the transformer registry
under its code, and can then be used like any other transformer (in a `TransformerTask`, in a `mapping`, ...).

YAML Configuration
------------------

```yaml
clever_age_process:
    generic_transformers:
        <transformer_code>:
            contextual_options:
                <contextual_option_code>:
                    required: <true|false>
                    default: <any>
                    default_is_null: <true|false>
            transformers:
                <transformers_list>
```

Options
-------

For each contextual option, you can define

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `required` | `bool` | | `true` | Indicates if the option is required or not |
| `default` | `any` | | `null` | If not `null`, define the default value |
| `default_is_null` | `bool` | | `false` | If you need `null` to be the default value, use this option |

Note that an option with a default value is still required by default, which has no effect since the default is used:
set `required: false` for an option without default value that may be omitted.

The `transformers` list uses the same syntax as any other transformer using a sub-list of transformers (see
[TransformerTrait](traits/transformer_trait.md)).
You can use the syntax for contextual values (`{{ contextual_option_code }}`) to put placeholders that will be filled by
those contextual options, with the values given when the generic transformer is used. If the whole value is a
placeholder, the raw option value is injected (it can be an array, an integer...).

Example
-------

```yaml
clever_age_process:
    generic_transformers:
        app_slug_prefix:
            contextual_options:
                prefix: ~              # Required option
                separator:
                    default: '-'
            transformers:
                slugify: ~
                sprintf:
                    format: '{{ prefix }}{{ separator }}%s'

    configurations:
        app.demo:
            tasks:
                transform:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    slug:
                                        code: '[name]'
                                        transformers:
                                            app_slug_prefix:
                                                prefix: product
```

As any transformer code, generic transformer codes must be unique: use a prefix to avoid conflicts.
