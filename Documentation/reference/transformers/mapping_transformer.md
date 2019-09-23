MappingTransformer
==================

Transform a set of properties into a (possibly) new output.

Basically, the algorithm is:
* determine destination (from `initial_value` or `keep_input`)
* foreach property
  - get value(s) from the source(s) (from `code`, `constant` or `set_null`)
  - use additional transformers on the value
  - merge the property and its value into the destination (with `merge_callback`, the property accessor, or as a simple array index)

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\MappingTransformer`
* **Transformer code**: `mapping`

Accepted inputs
---------------

`array` or `object` that can be accessed by the property accessor

Possible outputs
----------------

`array` or `object` (the "destination") containing the property manipulated by the transformer 

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `mapping` | `array` | **X** | | List of property => sub-mapping options. The property code can be a single string to be used as an array index, or a writable property path |
| `ignore_missing` | `bool` | | `false` | Ignore property accessor errors for the whole mapping |
| `keep_input` | `bool` | | `false` | Use input as the mapping destination (takes precedence on `initial_value`). Keep in mind that due to PHP behavior, arrays are cloned while objects are passed by reference |
| `initial_value` | `any` | | `[]` | Set the mapping destination |
| `merge_callback` | `callable` or `null` | | `null` | Allow to change how a property can be set in the destination |

Foreach property there is the following options.

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `code` | `string` or `array` or `null` | | `null` | A property path, or a list of property path. By default it would be the same as the destination property. Will be used as a source. The special value '.' access the whole object. |
| `constant` | `any` | | `null` | If not `null`, will be directly used as a source (takes precedence on `code`) |
| `set_null` | `bool` | | `false` | If `true`, `null` will be directly used as a source (takes precedence on `code`) |
| `ignore_missing` | `bool` | | `false` | Ignore property accessor errors for this source |
| `transformers` | `array` | | `[]` | List of transformer code => transformer options for subsequent transformations |

Examples
--------

* Simple transformation, will output an array with keys "code", "label", "type", "reference", "required" and "slug"
  - required input: an array with keys "Code", "label", "Type", "Name" and "ID"
  - output: an array with keys "code", "label", "type", "reference", "required" and "slug"

```yaml
transform_data:                                                              # Task level
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    options:
        transformers:
            mapping:
                mapping:
                    code:                                                   # Simple mapping from "Code" to "code"
                        code: '[Code]'
                    "[label]": ~                                            # Value from "label" will be kept with the same name
                    type:                                                   # Get value from "type" and map values (with a default)
                        code: '[Type]'
                        transformers:
                            convert_value:
                                ignore_missing: true
                                map:
                                    TEXTE:            text
                                    NUMERIQUE:        number
                                    LISTE_DEROULANTE: simpleselect
                                    CHOIX_MULTIPLES:  multiselect
                                    DATE:             date
                            default:
                                value: unknown
                    reference:                                              # "null" column
                        set_null: true
                    required:                                               # "true" column
                        constant: true
                    slug:                                                   # Get multiple sources, slugify them, and merge them
                        code:
                            name: '[Name]'
                            id:   '[ID]'
                        transformers:
                            array_map: 
                                transformers:
                                    slugify: ~
                            implode:
                                separator: '_'
    outputs: [next_task]
```

* Mapping in depth, using objects
  - required input: an object with an iterable property "productItems", containing objects with property "longName"
  - output: an array with key "items", containing a list of array with key "name"

```yaml
transform_data:                                                             # Task level
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    options:
        transformers:                                                       # TransformerTask options
            mapping:                                                        # Transformer code
                mapping:                                                    # MappingTransformer options
                    items:                                                  # property code
                        code: 'productItems'                                # property options
                        transformers:                                       # property options
                            array_map:                                      # Transformer code
                                transformers:                               # ArrayMapTransformer options
                                    mapping:                                # Transformer code
                                        mapping:                            # MappingTransformer options
                                            name:                           # property code
                                                code: 'longName'            # property options
                        
    outputs: [next_task]
```

* Advanced property setter
  - required input: an object with a property "address", containing an object with properties "postCode" and "customer", itself containing an object with property "hasFlag" 
  - output: same object, with a modified "address.customer.hasFlag"

```yaml
transform_data:                                                             # Task level
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    options:
        transformers:
            mapping:
                keep_input: true
                mapping:
                    address.customer.hasFlag:
                        code: address.postCode
                        transformers:
                            convert_value:
                                ignore_missing: true
                                map:
                                    69005: true
                            default:
                                value: false
    outputs: [next_task]
```
