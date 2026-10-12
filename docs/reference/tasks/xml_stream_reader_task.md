XmlStreamReaderTask
===================

Reads an XML file element by element with [XMLReader](https://www.php.net/manual/en/class.xmlreader.php) and iterates
over the elements with a given name, outputting each of them as a `\DOMElement`. Unlike
[XmlReaderTask](xml_reader_task.md), the whole file is never loaded in memory: use it for large XML files (product
catalogs, ERP exports...).
Requires the `dom` and `xmlreader` PHP extensions.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Xml\XmlStreamReaderTask`
* **Iterable task**

Accepted inputs
---------------

`string`: path of the file to read, only used when the `file_path` option is not set (ignored otherwise).

Possible outputs
----------------

`\DOMElement`: for each element named `node_name`, the element expanded with
[XMLReader::expand](https://www.php.net/manual/en/xmlreader.expand.php), as the root element of its own
`\DOMDocument`. It can be passed directly to the [XpathEvaluatorTransformer](../transformers/xpath_evaluator_transformer.md), with
queries relative to the element (e.g. `@id`, `name/text()`).

When the file contains no matching element, no output is produced and the task is skipped.

Options
-------

| Code        | Type           | Required | Default | Description                                                                                                                          |
|-------------|----------------|:--------:|---------|--------------------------------------------------------------------------------------------------------------------------------------|
| `file_path` | `string\|null` |          | `null`  | Path of the file to read. When `null`, the path is read from the input                                                               |
| `node_name` | `string`       |  **X**   |         | Qualified name of the elements to iterate on, as written in the file (e.g. `product`, or `g:item` for an element with a prefix) |

Examples
--------

```yaml
# Task configuration level
read:
  service: '@CleverAge\ProcessBundle\Task\File\Xml\XmlStreamReaderTask'
  options:
    file_path: '%kernel.project_dir%/var/data/products.xml'
    node_name: product
  outputs: [transform]
transform:
  service: '@CleverAge\ProcessBundle\Task\TransformerTask'
  options:
    transformers:
      xpath_evaluator:
        query:
          sku: '@sku'
          name: 'name/text()'
  outputs: [write]
```

Notes
-----

* Elements are matched at any depth. The subtree of a matching element is not searched again: an element named
  `node_name` nested inside a matching element is part of its parent output, and is not output on its own.
* Namespaces declared on ancestors are copied to the expanded element, so prefixed queries (e.g. `g:price/text()`)
  still work.
* An invalid XML content (not well-formed, empty file...) throws an `\UnexpectedValueException` whose message contains
  the libxml error(s) with their line and column, handled by the task `error_strategy`. As the file is read as a
  stream, the elements located before the error have already been output. libxml warnings are tolerated.
* A missing or unreadable file, or no file path in the option nor in the input, throws an `\UnexpectedValueException`.
* The file is released at the end of the iteration (and on `finalize()`), so that the task can read it again on its next
  input.
* Use [XmlReaderTask](xml_reader_task.md) to get the whole document at once (small files).
