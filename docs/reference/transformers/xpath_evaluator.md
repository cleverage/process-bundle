XpathEvaluatorTransformer
=========================

Manipulate a DOMNode to extract some information using xpath.
Requires `php-xml`.

**Important** : due to the [behavior of `\DOMXpath::query`](https://www.php.net/manual/en/domxpath.query.php), if you want
to make a query on a sub element of the full `\DOMDocument` you need to start your query with a `.` to specify the current node.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\Xml\XpathEvaluatorTransformer`
* **Transformer code**: `xpath_evaluator`

Accepted inputs
---------------

`\DOMNode` only.

Possible outputs
----------------

Depending on the options :
- `string`
- `\DOMNode`
- `null`
- an `array` of one of the type above

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `query` | `string` or `array` | **X** | | One or multiple Xpath queries. Using an array, you can either have a simple list of subqueries, or override some root-level query options |
| `single_result` | `boolean` |  | `true` | Force the result to match a single value |
| `ignore_missing` | `boolean` | | `true` | Only used with `single_result`, avoid errors if the query doesn't match anything |
| `unwrap_value` | `boolean` | | `true` | Return the textual content of the node, only works if the result is a `\DOMText` (you might need to use the `text()` xpath selector) or a `\DOMAttr` |

Subqueries, in their complex form, have the following options :

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `subquery` | `string` | **X** | | An Xpath query, no additional sublevel is allowed |
| `single_result` | `boolean` |  | _Root-level value for `single_result`_ | Force the result to match a single value |
| `ignore_missing` | `boolean` | | _Root-level value for `ignore_missing`_ | Only used with `single_result`, avoid errors if the query doesn't match anything |
| `unwrap_value` | `boolean` | | _Root-level value for `unwrap_value`_ | Return the textual content of the node, only works if the result is a `\DOMText` (you might need to use the `text()` xpath selector) or a `\DOMAttr` |


Examples
--------

All examples assume this XML
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
        <g>ok6</g>
    </d>
</a>
```

* Example 1 : get a single value
  
```yaml
# Transformer options level
xpath_evaluator:
    query: '/a/b/c[0]/text()'
```

* Example 2 : get a multiple values
  
```yaml
# Transformer options level
xpath_evaluator:
    query: '/a/b/c/text()'
    single_result: false
```

```yaml
# Transformer options level
xpath_evaluator:
    query: 
        - '/a/d/e/text()'
        - '/a/d/f/text()'
        - '/a/d/g/text()'
```

* Example 3 : get a `\DOMNode`

```yaml
# Transformer options level
xpath_evaluator:
    query: '/a/b'
    unwrap_value: false
```

* Example 4 : subquery with partially overridden options

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
