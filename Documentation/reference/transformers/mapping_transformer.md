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
| `keep_input` | `bool` | | `false` | Use input as the mapping destination (takes precedence on `initial_value`) |
| `initial_value` | `any` | | `[]` | Set the mapping destination |
| `merge_callback` | `callable` or `null` | | `null` | Allow to change how a property can be set in the destination |

Foreach property there is the following options.

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `code` | `string` or `array` or `null` | | `null` | A property path, or a list of property path. By default it would be the same as the destination property. Will be used as a source. |
| `constant` | `any` | | `null` | If not `null`, will be directly used as a source (takes precedence on `code`) |
| `set_null` | `bool` | | `false` | If `true`, `null` will be directly used as a source (takes precedence on `code`) |
| `ignore_missing` | `bool` | | `false` | Ignore property accessor errors for this source |
| `transformers` | `array` | | `[]` | List of transformer code => transformer options for subsequent transformations |

