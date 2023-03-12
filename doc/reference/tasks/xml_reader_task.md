XmlReaderTask
=============

Open and read an XML file.
Requires `php-xml`.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Xml\XmlReaderTask`

Accepted inputs
---------------

No input accepted.

Possible outputs
----------------

A `\DOMDocument` built from the file.

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `file_path` | `string` | **X** | | Path of the file to read from (relative to symfony root or absolute) |
| `mode` | `string` | | `rb` | File open mode (see [fopen mode parameter](https://secure.php.net/manual/en/function.fopen.php)) |

Examples
--------

```yaml
# Task configuration level
my_xml_reader:
    service: '@CleverAge\ProcessBundle\Task\File\Xml\XmlReaderTask'
    options: 
        file_path: '%kernel.project_dir%/var/data/file.xml'
```
