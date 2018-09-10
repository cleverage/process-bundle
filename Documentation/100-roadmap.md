UPGRADE TO 2.0
==============

Task Logging
------------

Instead of using `CleverAge\ProcessBundle\Model\ProcessState::log` you must now use the standard
`Psr\Log\LoggerInterface` with the `cleverage_process_task` chanel. You should also pass 
`CleverAge\ProcessBundle\Model\ProcessState::getLogContext` to the log context.

TransformerTask
---------------

The main option is now "transformers", which accept transformer codes and then transformer options.
Default options should now look like: 
```yaml
options:
    transformers:
        mapping:
            mapping:
                <key>: <options>
```


UPGRADE TO 1.1
==============

MappingTransformer
------------------

* The option "ignore_extra" is renamed to "keep_input".

Planned (v2+)
============

* automated transformer creation & refactoring
    * easy test cases via yml ?
* changes in interfaces
    * allow blocking + iterable
* FIFO queues for in/out
* Allow to hide process (private mode), write small documentation in YML
