XmlWriterTask
=============

Writes the `\DOMDocument` received as input to an XML file, whose path is set in the options.
Requires the `dom` PHP extension.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Xml\XmlWriterTask`

Accepted inputs
---------------

`\DOMDocument`: document to dump with [DOMDocument::saveXML](https://www.php.net/manual/en/domdocument.savexml.php).
An `\UnexpectedValueException` is thrown for any other type.

Possible outputs
----------------

`string`: path of the written file (value of the `file_path` option).

Options
-------

| Code        | Type     | Required | Default | Description                                                                                   |
|-------------|----------|:--------:|---------|-----------------------------------------------------------------------------------------------|
| `file_path` | `string` |  **X**   |         | Path of the file to write to                                                                  |
| `mode`      | `string` |          | `wb`    | File open mode (see [fopen mode parameter](https://www.php.net/manual/en/function.fopen.php)) |

Examples
--------

```yaml
# Task configuration level
write_xml:
  service: '@CleverAge\ProcessBundle\Task\File\Xml\XmlWriterTask'
  options:
    file_path: '%kernel.project_dir%/var/data/file.xml'
```

Notes
-----

* The file is opened on each execution: with the default `wb` mode, each input overwrites the file.
