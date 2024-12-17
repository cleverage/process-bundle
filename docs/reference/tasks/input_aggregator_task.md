InputAggregatorTask
===============

Accumulate heterogeneous inputs. It skips output until input is received from every parent task. Then, the output is flush (except for `keep_inputs` indexes).

Warning : the `clean_input_on_override` option can be dangerous if set to `false`. Especially in loops (iterable process), there can be cases where the inputs are mixed between the iterations of an input... (ex: one of the parent task has skipped output due to an error). Even on `true`, some case have been determined to be problematic.

The usage of this task is therefore **strongly discouraged**, unless you are using it in a non-iterable process. It may one day evolve in a Blocking Task.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\InputAggregatorTask`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`array`: list of index destination => values from previous tasks

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `input_codes` | `array` | **X** | | List of task code => index destination |
| `clean_input_on_override` | `bool` | | `true` | Empty the future output if there any override |
| `keep_inputs` | `array` or `null` | | `null` | List of index destination to keep on flush |

