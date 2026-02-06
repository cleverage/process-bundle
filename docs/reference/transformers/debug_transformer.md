DebugTransformer
================

Dump the current value using Symfony's VarDumper and pass it through unchanged. Useful for debugging transformer
chains.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\DebugTransformer`
* **Transformer code**: `dump`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: same as input (passthrough)

Options
-------

No options.

Examples
--------

```yaml
# Transformer options level
dump: ~
```
