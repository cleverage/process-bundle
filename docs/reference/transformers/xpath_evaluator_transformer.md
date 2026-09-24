XpathEvaluatorTransformer
=========================

Evaluate one or several XPath queries on a `\DOMNode` and return the matching values or nodes. Requires the `dom` PHP
extension.

Queries are evaluated with [\DOMXPath::query](https://www.php.net/manual/en/domxpath.query.php), using the input node
as context node: to query relatively to a sub element of the document, start the query with `.` (e.g. `./c/text()`);
an absolute query (starting with `/`) always searches the whole document.

Transformer reference
---------------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Xml\XpathEvaluatorTransformer`
* **Transformer code**: `xpath_evaluator`

Accepted inputs
---------------

`\DOMNode` (including `\DOMDocument`). Any other value throws an `\UnexpectedValueException`.

Possible outputs
----------------

For a single query, depending on the options:

* `string`: text of the matching `\DOMText` or `\DOMAttr` (`single_result` and `unwrap_value` are `true`)
* `\DOMNode`: the matching node (`single_result` is `true`, `unwrap_value` is `false`)
* `null`: nothing matched (`single_result` and `ignore_missing` are `true`)
* `array` of the above when `single_result` is `false`

When `query` is an array, the output is an `array` with the same keys, each value being the result of the matching
subquery.

Options
-------

| Code             | Type            | Required | Default | Description                                                                                                                              |
|------------------|-----------------|:--------:|---------|------------------------------------------------------------------------------------------------------------------------------------------|
| `query`          | `string\|array` |  **X**   |         | An XPath query, or an array of subqueries (see below)                                                                                    |
| `single_result`  | `bool`          |          | `true`  | Return a single value instead of a list; more than one result throws an `\UnexpectedValueException`                                      |
| `ignore_missing` | `bool`          |          | `true`  | Only used with `single_result`: return `null` when nothing matches, instead of throwing an `\UnexpectedValueException`                   |
| `unwrap_value`   | `bool`          |          | `true`  | Return the text value of each result; only `\DOMText` (use the `text()` selector) and `\DOMAttr` results are supported, others throw     |

Each element of an array `query` is either a string (the subquery itself, using the root level options), or an array
with the following options:

| Code             | Type     | Required | Default                        | Description                                                         |
|------------------|----------|:--------:|--------------------------------|---------------------------------------------------------------------|
| `subquery`       | `string` |  **X**   |                                | An XPath query (no further nesting is allowed)                      |
| `single_result`  | `bool`   |          | _root level `single_result`_   | Same as the root level option, for this subquery only               |
| `ignore_missing` | `bool`   |          | _root level `ignore_missing`_  | Same as the root level option, for this subquery only               |
| `unwrap_value`   | `bool`   |          | _root level `unwrap_value`_    | Same as the root level option, for this subquery only               |

Examples
--------

All examples use this XML document as input:

```xml
<a>
    <b>
        <c>ok1</c>
        <c>ok2</c>
        <c>ok3</c>
    </b>
    <d>
        <e>ok4</e>
        <f>ok5</f>
        <g id="g1">ok6</g>
    </d>
</a>
```

* Get a single value: `'ok1'` (XPath positions start at 1)

```yaml
# Transformer options level
xpath_evaluator:
  query: '/a/b/c[1]/text()'
```

* Get an attribute value: `'g1'`

```yaml
# Transformer options level
xpath_evaluator:
  query: '/a/d/g/@id'
```

* Get multiple values: `['ok1', 'ok2', 'ok3']`

```yaml
# Transformer options level
xpath_evaluator:
  query: '/a/b/c/text()'
  single_result: false
```

* Get a list of subqueries results: `['ok4', 'ok5', 'ok6']`

```yaml
# Transformer options level
xpath_evaluator:
  query:
    - '/a/d/e/text()'
    - '/a/d/f/text()'
    - '/a/d/g/text()'
```

* Get a `\DOMNode` (the `<b>` element)

```yaml
# Transformer options level
xpath_evaluator:
  query: '/a/b'
  unwrap_value: false
```

* Named subqueries, partially overriding root level options:
  `{all_c_values: ['ok1', 'ok2', 'ok3'], e_value: 'ok4', f_value: 'ok5'}`

```yaml
# Transformer options level
xpath_evaluator:
  query:
    all_c_values:
      subquery: '/a/b/c/text()'
      single_result: false
    e_value: '/a/d/e/text()'
    f_value:
      subquery: '/a/d/f/text()'
```
