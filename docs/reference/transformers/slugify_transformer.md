SlugifyTransformer
==================

Convert a string into a slug. The value is transliterated with a
[\Transliterator](https://www.php.net/manual/en/class.transliterator.php) (by default, accents are removed), HTML
tags are stripped (unless `strip_tags` is `false`), the result is trimmed and lowercased (multibyte characters included), every sequence of characters matching `replace` is replaced by
`separator`, and leading and trailing separators are removed.

Requires the `intl` PHP extension (suggested by the bundle): without it, the transformer fails with an explicit message.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\SlugifyTransformer`
* **Transformer code**: `slugify`

Accepted inputs
---------------

`string`, or a scalar or `\Stringable` cast to string. `null`, an array or another object throws an
`UnexpectedValueException`.

Possible outputs
----------------

`string`

Options
-------

| Code             | Type     | Required | Default                                  | Description                                                                             |
|------------------|----------|:--------:|------------------------------------------|-----------------------------------------------------------------------------------------|
| `transliterator` | `string` |          | `'NFD; [:Nonspacing Mark:] Remove; NFC'` | Transliterator identifier, passed to `\Transliterator::create()` (see Notes)            |
| `replace`        | `string` |          | `'/[^a-z0-9]+/'`                         | Regular expression of the characters to replace (applied on the lowercased string)      |
| `separator`      | `string` |          | `'_'`                                    | Replacement string, also trimmed from both ends of the result                           |
| `strip_tags`     | `bool`   |          | `true`                                   | Strip the HTML tags with `strip_tags()`, which removes everything after a `<` that does not open a tag (`'Tom<Jerry'` becomes `'tom'`, `'<3 love'` becomes `''`): set it to `false` for a text that is not HTML |

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

Notes
-----

The default transliterator only removes the accents (the combining marks): the letters that are not decomposed into a
base letter and an accent are then removed by the default `replace` pattern (`'Cœur'` becomes `'c_ur'`, `'Straße'`
`'stra_e'`, `'Łódź'` `'odz'`), and a text without latin letters gives `''`. `'Any-Latin; Latin-ASCII'` is recommended:
it transliterates them (`'coeur'`, `'strasse'`, `'lodz'`) and the other scripts too (see the example above).

An invalid `transliterator` identifier (rejected by `\Transliterator::create()`), or an option that is not a string,
raises an `InvalidOptionsException` when the options are resolved, i.e. when the transformer is configured, not on the
first transformed value.

A value that cannot be transliterated (e.g. invalid UTF-8) throws an `UnexpectedValueException`.
