Generic transformers definition
===============================

YAML Configuration
------------------

```yaml
clever_age_process:
    generic_transformers:
        <transformer_code>:
            contextual_options:
                <contextual_option_code>:
                    required:
                    default:
                    default_is_null:
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

The transformer options are the same than any other transformer using a sub-list of transformers (see [TransformerTrait](traits/transformer_trait.md)). 
You can use the syntax for contextual values (`{{ contextual_option_code }}`) to put placeholders that will be filled by
those contextual options.
