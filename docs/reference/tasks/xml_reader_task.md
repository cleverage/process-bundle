XmlReaderTask
=============

Reads an XML file, whose path is set in the options, and outputs it as a `\DOMDocument`.
Requires the `dom` PHP extension.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Xml\XmlReaderTask`

Accepted inputs
---------------

Input is ignored (a warning is logged if an input is given).

Possible outputs
----------------

`\DOMDocument`: document loaded from the file content with
[DOMDocument::loadXML](https://www.php.net/manual/en/domdocument.loadxml.php).

Options
-------

| Code        | Type     | Required | Default | Description                                                                                   |
|-------------|----------|:--------:|---------|-----------------------------------------------------------------------------------------------|
| `file_path` | `string` |  **X**   |         | Path of the file to read                                                                      |
| `mode`      | `string` |          | `rb`    | File open mode (see [fopen mode parameter](https://www.php.net/manual/en/function.fopen.php)) |

Examples
--------

```yaml
# Task configuration level
read_xml:
  service: '@CleverAge\ProcessBundle\Task\File\Xml\XmlReaderTask'
  options:
    file_path: '%kernel.project_dir%/var/data/file.xml'
  outputs: [transform]
```

Notes
-----

* The result of `loadXML()` is not checked: an invalid XML content raises libxml warnings (which may be converted to
  exceptions by the Symfony error handler) and produces an empty or partial document.
* An empty file raises a `\ValueError` (the file content is read with `fread()` using the file size as length).
