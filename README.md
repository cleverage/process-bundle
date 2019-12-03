CleverAge/ProcessBundle
=======================

## Introduction

This bundle allows to configure series of tasks to be performed on a certain order.
Basically, it will greatly ease the configuration of import and exports but can do much more.

## Index

- [Quick start](Documentation/01-quick_start.md)
- [Task types](Documentation/02-task_types.md)
- [Custom tasks and development](Documentation/03-custom_tasks.md)
- [Advanced workflow](Documentation/04-advanced_workflow.md)
- [Good practices]
- [Testing]
- [Contribute](CONTRIBUTING.md)
- Cookbooks
    - [Common Setup](Documentation/cookbooks/01-common_setup.md)
    - [Transformations]
    - [Flow manipulation]
    - [Dummy tasks]
    - [Debugging]
    - [Logging]
    - [Subprocess]
    - [File manipulation]
    - [Direct call (in controller)]
- Reference
    - [Process definition](Documentation/reference/01-process_definition.md)
    - [Task definition](Documentation/reference/02-task_definition.md)
      - Basic and debug
        - [ConstantOutputTask](Documentation/reference/tasks/constant_output_task.md)
        - [ConstantIterableOutputTask](Documentation/reference/tasks/constant_iterable_output_task.md)
        - [DebugTask](Documentation/reference/tasks/debug_task.md)
        - [DummyTask](Documentation/reference/tasks/dummy_task.md)
        - [EventDispatcherTask](Documentation/reference/tasks/event_dispatcher_task.md)
      - Data manipulation and transformations
        - [DenormalizerTask](Documentation/reference/tasks/denormalizer_task.md)
        - [NormalizerTask](Documentation/reference/tasks/normalizer_task.md)
        - [PropertyGetterTask](Documentation/reference/tasks/property_getter_task.md)
        - [PropertySetterTask](Documentation/reference/tasks/property_setter_task.md)
        - [TransformerTask](Documentation/reference/tasks/transformer_task.md)
      - File/CSV
        - [CsvReaderTask](Documentation/reference/tasks/csv_reader_task.md)
        - [CsvWriterTask](Documentation/reference/tasks/csv_writer_task.md)
      - File/XML
        - [XmlReaderTask](Documentation/reference/tasks/xml_reader_task.md)
      - Flow manipulation
        - [AggregateIterableTask](Documentation/reference/tasks/aggregate_iterable_task.md)
        - [InputAggregatorTask](Documentation/reference/tasks/input_aggregator_task.md)
        - [InputIteratorTask](Documentation/reference/tasks/input_iterator_task.md)
    - Transformers
        - [ArrayFilterTransformer](Documentation/reference/transformers/array_filter_transformer.md)
        - [MappingTransformer](Documentation/reference/transformers/mapping_transformer.md)
        - [RulesTransformer](Documentation/reference/transformers/rules_transformer.md)
        - [DateFormatTransformer](Documentation/reference/transformers/date_format.md)
        - [DateParserTransformer](Documentation/reference/transformers/date_parser.md)
        - [XpathEvaluatorTransformer](Documentation/reference/transformers/xpath_evaluator.md)
    - [Generic transformers definition](Documentation/reference/03-generic_transformers_definition.md)
- Examples
    - [Simple ETL]
- [Roadmap and versions](Documentation/100-roadmap.md)


-------

_obsolete documentation_

## Configuration reference

### Defining processes
```yml
clever_age_process:
    configurations:
        <process_code>:
            options: ~ # Global options for the whole process, not currently used
            entry_point: <task_code> # Code of the first task to execute
            tasks: # See the next chapter
                <task_code>:
                    # You can use two syntax for service declaration
                    service: '@<reference of the service>'
                    
                    # Or, alternatively, if you don't want to declare unecessary services if no argument is needed to construct this task
                    service: MyNamespace\FooBarBundle\Task\MyTask
                    
                    # In both cases the service/class must implements the TaskInterface
                    
                    # Options to pass to the task, see each task for more information
                    options: {}
                    
                    # List of the tasks to pass the output to
                    outputs: [<other_task_code>, ...]
                    
                    # Other possible values are: 'stop' and 'continue'
                    error_strategy: skip
                    
                    # Logs any errors encountered
                    log_level: critical

                # More tasks
```
Note that orphan tasks will be reported as errors before the process starts

### Existing tasks

#### StatCounterTask
Accepts an array or an object as an input and sets values before returning it as the output.
At the end of the process, during the finalize(), it will log the number of item processed.
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Reporting\StatCounterTask'
```
No supported options, no output.

#### ValidatorTask
Validate data from the input and pass it to the output
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Validation\ValidatorTask'
    outputs: [<task_code>] # Array of tasks accepting the same data than the input
```

## Creating a custom task

### Creating the class

```php
<?php

namespace MyNamespace\FooBarBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

class MyTask implements TaskInterface
{
    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        // Do stuff
    }
}
```

### Configuring the service

```yml
services:
    my_namespace.task.task_code:
        class: MyNamespace\FooBarBundle\Task\MyTask
        shared: false
```
Unless you want to share the same service between multiple tasks with the same service reference, we strongly recommend
to configure your tasks services as ```shared: false```.

## Example

Basic export to CSV process

```yml
clever_age_process:
    configurations:
        data_export:
            entry_point: read
            tasks:
                read:
                    service: '@CleverAge\DoctrineProcessBundle\Task\EntityManager\DoctrineReaderTask'
                    options:
                        class_name: MyNamespace\FooBarBundle\Entity\Data
                    outputs: [normalize]

                normalize:
                    service: '@CleverAge\ProcessBundle\Task\Serialization\NormalizerTask'
                    options:
                        format: csv
                    outputs: [write]

                write:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
                    options:
                        file_path: '%kernel.root_dir%/../var/data/export/data.csv'
```

## Known issues

For now there are some issue with BlockingTask in multi-branch workflow

* An IterableTask cannot have 2 children BlockingTask : only the last BlockingTask will be proceeded
* A BlockingTask will not be proceeded if it has not a direct ancestor IterableTask 
* A BlockingTask will be proceeded as much as there is direct IterableTask ancestors

If you want to avoid any problem, use BlockingTask only in a one-branch workflow, with only one preceding IterableTask :

* Task -> Iterable -> Task -> Task -> Blocking -> Task

## Release notes

### v1.1

* Fixed issues with blocking tasks
* Removed deprecated methods [...]
* added input/output in process manager (may allow a start_process_task)

New issues :
* Error workflow
