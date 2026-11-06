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
set `required: false` for an option without default value that may be omitted: when omitted, its placeholders are
replaced by `null`.

The `transformers` list uses the same syntax as any other transformer using a sub-list of transformers, a map or a list
(see [TransformerTrait](traits/transformer_trait.md)).
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
                read:
                    task: constant_output
                    options:
                        output: { name: 'My Product' }
                    outputs: [transform]
                transform:
                    task: transformer
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    slug:
                                        code: '[name]'
                                        transformers:
                                            app_slug_prefix:
                                                prefix: product
                    outputs: [debug]
                debug:
                    task: debug
```

`bin/console cleverage:process:execute app.demo` dumps `["slug" => "product-my_product"]`.

As any transformer code, generic transformer codes must be unique: use a prefix to avoid conflicts. A dash is kept in
the code (it was replaced by an underscore before 5.7, the former name is deprecated), as in the codes of the
transformers and of the contextual options of a generic transformer (replaced by an underscore before 5.9, unless the
code also had an underscore: `array-first` was found as the `array_first` transformer, and the `the-prefix` contextual
option was given and used as `the_prefix` / `{{ the_prefix }}`; these former names still work but are deprecated since
5.10, write the code of the transformer and of the contextual option as registered).
