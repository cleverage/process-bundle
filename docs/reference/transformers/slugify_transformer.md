SlugifyTransformer
==================

Convert a string into a slug. The value is transliterated with a
[\Transliterator](https://www.php.net/manual/en/class.transliterator.php) (by default, accents are removed), HTML
tags are stripped, the result is trimmed and lowercased, every sequence of characters matching `replace` is replaced by
`separator`, and leading and trailing separators are removed.

Requires the `intl` PHP extension.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\SlugifyTransformer`
* **Transformer code**: `slugify`

Accepted inputs
---------------

`string`

Possible outputs
----------------

`string`

Options
-------

| Code             | Type     | Required | Default                                  | Description                                                                             |
|------------------|----------|:--------:|------------------------------------------|-----------------------------------------------------------------------------------------|
| `transliterator` | `string` |          | `'NFD; [:Nonspacing Mark:] Remove; NFC'` | Transliterator identifier, passed to `\Transliterator::create()`                        |
| `replace`        | `string` |          | `'/[^a-z0-9]+/'`                         | Regular expression of the characters to replace (applied on the lowercased string)      |
| `separator`      | `string` |          | `'_'`                                    | Replacement string, also trimmed from both ends of the result                           |

Examples
--------

* `'Hélène Dupont'` becomes `'helene_dupont'`

```yaml
# Transformer options level
slugify: ~
```

* Use a dash as separator, and also transliterate non-latin characters: `'Привет мир'` becomes `'privet-mir'`

```yaml
# Transformer options level
slugify:
  transliterator: 'Any-Latin; Latin-ASCII'
  separator: '-'
```
