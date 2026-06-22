InstantiateTransformer
======================

Create a new object instance of the configured class, using the input array values as constructor arguments.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Object\InstantiateTransformer`
* **Transformer code**: `instantiate`

Accepted inputs
---------------

`array`: values passed as constructor arguments (in order)

Possible outputs
----------------

`object`: a new instance of the configured class

Options
-------

| Code    | Type     | Required | Default | Description                              |
|---------|----------|:--------:|---------|------------------------------------------|
| `class` | `string` | **X**    |         | Fully qualified class name to instantiate |

Examples
--------

```yaml
# Transformer options level
instantiate:
  class: 'App\DTO\ProductData'
```
