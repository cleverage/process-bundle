TransformerTask
===============

Pass an input into a chain of transformers.

See transformers references.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\TransformerTask`

Accepted inputs
---------------

`any`: it should match the 1st expected input of the transform chain

Possible outputs
----------------

`any`: result of the transform chain

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `transformers` | `array` | **X** | | List of transformers, see [TransformerTrait](../traits/transformer_trait.md) |

