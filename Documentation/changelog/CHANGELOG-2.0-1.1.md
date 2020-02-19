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

Other
-----

* Fixed issues with blocking tasks
* Removed deprecated methods
* added input/output in process manager (may allow a start_process_task)

New issues :
* Error workflow

Planned (v2+)
============

* automated transformer creation & refactoring
    * easy test cases via yml ?
* changes in interfaces
    * allow blocking + iterable
* FIFO queues for in/out
