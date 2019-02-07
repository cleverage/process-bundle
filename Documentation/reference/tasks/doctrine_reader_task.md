DoctrineReaderTask
==================

Reads data from a Doctrine Repository.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Addon\Doctrine\Task\EntityManager\DoctrineReaderTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

Iterate on an entity list returned by a Doctrine query.

Options
-------

All the criteria, order_by, limit and offset options behave like the [`EntityRepository::findBy`](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-objects.html#by-simple-conditions) method.

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `class_name` | `string` | **X** |  | Class name of the entity |
| `criteria` | `array` | | `[]` | List of field => value to use while matching entities |
| `order_by` | `array` | | `[]` | List of field => direction |
| `limit` | `int` or `null` | | `null` | Result max count |
| `offset` | `int` or `null` | | `null` | Result first item offset |
| `entity_manager` | `string` or `null` | | `null` | Use another entity manager than the default |

