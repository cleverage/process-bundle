DoctrineWriterTask
==================

Write a Doctrine entity to the database.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Task\Doctrine\DoctrineWriterTask`

Accepted inputs
---------------

Any doctrine managed entity.

Possible outputs
----------------

Re-output given entity.

Options
-------

| Command | Type | Required | Default | Description |
| ------- | ---- | :------: | ------- | ----------- |
| `entity_manager` | `string` or `null` | | `null` | Use another entity manager than the default |
| `global_flush` | `bool` | | `true` | Flush the whole entity manager after persist |

