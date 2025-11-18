SlugifyTransformer
=========================

Strip whitespace (or other characters) from the beginning and end of a string

This transformer uses the php internal function: https://www.php.net/manual/en/class.transliterator.php

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\SlugifyTransformer`
* **Transformer code**: `slugify`

Accepted inputs
---------------

Any value that can be cast to string.

Possible outputs
----------------

`string`

Options
-------

| Code             | Type     | Required  | Default                                | Description                    |
|------------------|----------|:---------:|----------------------------------------|--------------------------------|
| `transliterator` | `string` |           | `NFD; [:Nonspacing Mark:] Remove; NFC` | Used to create \Transliterator |
| `replace`        | `string` |           | `/[^a-z0-9]+/`                         | Used on preg_replace           |
| `separator`      | `string` |           | `_`                                    | Used on preg_replace           |

Examples
--------

```yaml
# Transformer mapping level
slug:
  code:
    - '[firstname]'
  transformers:
    slugify: ~
```
