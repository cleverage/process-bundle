InstantiateTransformer
======================

Create a new instance of the configured class, using the input array values as constructor arguments
(`\ReflectionClass::newInstanceArgs()`).

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Object\InstantiateTransformer`
* **Transformer code**: `instantiate`

Accepted inputs
---------------

`array`: the constructor arguments. A list is passed positionally; string keys are used as named arguments. Any other
value throws an `\UnexpectedValueException`.

Possible outputs
----------------

`object`: a new instance of the configured class

Options
-------

| Code    | Type     | Required | Default | Description                                |
|---------|----------|:--------:|---------|--------------------------------------------|
| `class` | `string` |  **X**   |         | Fully qualified class name to instantiate  |

Examples
--------

* Build an `App\Dto\Price` object whose constructor is `__construct(float $amount, string $currency)`

```yaml
# Transformer mapping level
price:
  code:
    - '[amount]'
    - '[currency]'
  transformers:
    instantiate:
      class: 'App\Dto\Price'
```

* Same, input `{amount: 12.5, currency: 'EUR'}` being passed as named arguments

```yaml
# Transformer options level
instantiate:
  class: 'App\Dto\Price'
```
