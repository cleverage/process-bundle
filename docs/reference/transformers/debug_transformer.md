DebugTransformer
================

Dump the value with Symfony `VarDumper` (if the component is installed) and return it unchanged. Useful to inspect a
transformer chain.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\DebugTransformer`
* **Transformer code**: `dump`

Accepted inputs
---------------

`any`

Possible outputs
----------------

`any`: the input, unchanged.

Options
-------

This transformer has no option.

Examples
--------

```yaml
# Transformer options level
dump: ~
```

* Dump a value in the middle of a chain

```yaml
# Transformer mapping level
name:
  code: '[name]'
  transformers:
    trim: ~
    dump: ~
    slugify: ~
```
