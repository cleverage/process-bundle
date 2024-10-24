XmlWriterTask
=============

Open and write an XML file.
Requires `php-xml`.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\File\Xml\XmlWriterTask`

Accepted inputs
---------------

A `\DOMDocument` to dump into the file.

Possible outputs
----------------

Resulting file path.

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `file_path` | `string` | **X** | | Path of the file to write into (relative to symfony root or absolute) |
| `mode` | `string` | | `rb` | File open mode (see [fopen mode parameter](https://secure.php.net/manual/en/function.fopen.php)) |

Examples
--------

```yaml
# Task configuration level
my_xml_reader:
    service: '@CleverAge\ProcessBundle\Task\File\Xml\XmlWriterTask'
    options: 
        file_path: '%kernel.project_dir%/var/data/file.xml'
```
