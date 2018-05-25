CleverAge/ProcessBundle
=======================

## Introduction

This bundle allows to configure series of tasks to be performed on a certain order.
Basically, it will greatly ease the configuration of import and exports but can do much more.

## Index

- [Quick start](Documentation/01-quick_start.md)
- [Task types](Documentation/02-task_types.md)
- [Custom tasks and development]
- [Advanced worklow]
- [Good practices]
- [Testing]
- Cookbooks
    - [Transformations]
    - [Flow manipulation]
    - [Dummy tasks]
    - [Debugging]
    - [Logging]
    - [Subprocess]
    - [File manipulation]
    - [Direct call (in controller)]
- Reference
    - [Process definition]
    - Tasks
    - Transformers
- Examples
    - [Simple ETL]
- [Roadmap and versions]


-------

_obsolete documentation_


## Base concepts
- Process: A sets of tasks linked together that you can run in a terminal
- Task: A simple action, that can accepts an input and should return an output

A task is basically a service that implements the ```TaskInterface``` interface.
It can also implements other interfaces to trigger specific behaviors:

- InitializableTaskInterface: The initialize() method will be called at the very beginning of the process, before
executing anything.
- IterableTaskInterface: The task can return multiple items, (one at a time)
- BlockingTaskInterface: Specific task that must be executed after an iterable task, will wait for the iteration to end
before continuing to execute children tasks

The AbstractConfigurableTask class is a good way to go if you need to configure your task with options, you should then
override the ```AbstractConfigurableTask::configureOptions()``` method.

This bundle already implements a lot of basic tasks that can be used out of the box, see dedicated chapter.

## Running a process
You can execute one or multiple process in a chain:
```bash
$ bin/console cleverage:process:execute process_code1 process_code2
```

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
                    log_errors: true

                # More tasks
```
Note that orphan tasks will be reported as errors before the process starts

### Existing tasks

#### ConstantOutputTask
Simply outputs the same configured value all the time, ignores any input
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\ConstantOutputTask'
    options:
        # Required options
        output: <mixed> # Will always output the value configured here
    outputs: [<task_code>] # Array of tasks to pass the output to
```

#### ConstantIterableOutputTask
Same as ConstantOutputTask but only accepts an array of values and iterates over each element.
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
    options:
        # Required options
        output: <array> # Will iterate over the elements
    outputs: [<task_code>] # Array of tasks to pass the output to
```

#### CsvReaderTask
Reads a CSV file and iterate on each line, returning an array of key -> values
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask'
    options:
        # Required options
        file_path: <string> # Required, the path of the file to read from

        # Optional options
        delimiter: ';'
        enclosure: '"'
        escape: '\\'
        headers: null # Use this if you want to manually passed headers
        mode: 'r' # Used by fopen
    outputs: [<task_code>] # Array of tasks accepting an array as input
```

#### CsvWriterTask
Write to a CSV file, will wait until the end of the previous iteration (this is a blocking task) and outputs the file
path.
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
    options:
        # Required options
        file_path: <string> # Required, the path of the file to write to

        # Optional options
        delimiter: ';'
        enclosure: '"'
        escape: '\\'
        headers: null # Use this if you want to manually passed headers
        mode: 'r' # Used by fopen
        split_character: '|' # Tries to implode array values based on this character
    outputs: [<task_code>] # This task will output the filepath of the written file
```
If the tasks read anything else than an array as input the process will stops.

#### DebugTask
Dumps the input value to the console, obviously for debug purposes
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Debug\DebugTask'
```
No supported options, no output.

#### DoctrineReaderTask
Reads data from a Doctrine Repository, iterating over the results. Ignores any input.
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Doctrine\DoctrineReaderTask'
    options:
        # Required options
        class_name: <string> # Required, the class name of the entity

        # Optional options
        criteria: []
        order_by: []
        limit: null
        offset: null
        entity_manager: null # If the entity manager is not the default one, use this option
    outputs: [<task_code>] # Array of tasks accepting an entity as input
```
All the criteria, order_by, limit and offset options behave like the ```EntityRepository::findBy``` method.

#### DoctrineWriterTask
Write a Doctrine entity to the database.
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Doctrine\DoctrineWriterTask'
    options:
        # Optional options
        entity_manager: null # If the entity manager is not the default one, use this option
    outputs: [<task_code>] # Array of tasks accepting an entity as input
```

#### NormalizerTask
Normalize data from the input and pass it to the output
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Serialization\NormalizerTask'
    options:
        # Required options
        format: <string> # Required, format for normalization

        # Optional options
        context: [] # Will be passed directly to the third parameter of the normalize method
    outputs: [<task_code>] # Array of tasks accepting the normalized data as input
```

#### DenormalizerTask
Denormalize data from the input and pass it to the output
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Serialization\DenormalizerTask'
    options:
        # Required options
        class: <string>

        # Optional options
        format: <string>
        context: [] # Will be passed directly to the third parameter of the normalize method
    outputs: [<task_code>] # Array of tasks accepting the denormalized data as input
```

#### PropertySetterTask
Accepts an array or an object as an input and sets values before returning it as the output
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\PropertySetterTask'
    options:
        # Required options
        values:
            <property>: <mixed> # The value you want to set
            # ...
    outputs: [<task_code>] # Array of tasks accepting the same data as the input
```

#### StatCounterTask
Accepts an array or an object as an input and sets values before returning it as the output.
At the end of the process, during the finalize(), it will log the number of item processed.
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Reporting\StatCounterTask'
```
No supported options, no output.

#### TransformerTask
Accepts an array as input and sets values before returning it as the output
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    options:
        # Required options
        transformers:
            mapping: # the code of the transformer that you want to apply
                mapping:
                    <property>:
                        code: null # Source property, default to the key of the config
                        constant: null # If you want to output a constant value
                        set_null: false # Because the "null" value cannot be covered by the constant option
                        ignore_missing: false # Will ignore missing properties
                        transformers: # Applies a series of other transformers
                            <transformer_code>: [] # Transformer options
                    # ...
        
                # Optional options
                ignore_missing: false # Globally ignore any missing property
                ignore_extra: false # Ignore extra properties
                initial_value: [] # The value from which the transformer reset to before applying any mapping
    outputs: [<task_code>] # Array of tasks accepting an array as input
```

#### ValidatorTask
Validate data from the input and pass it to the output
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Validation\ValidatorTask'
    outputs: [<task_code>] # Array of tasks accepting the same data than the input
```

#### EventDispatcherTask
Call the Symfony event dispatcher
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\Event\EventDispatcherTask'
    options:
        event_name: <event_name> # The name of your event
```

#### DummyTask
Passes the input to the output, can be used as an entry point allow multiple tasks to be run at the entry point
```yml
<task_code>:
    service: '@CleverAge\ProcessBundle\Task\DummyTask'
    outputs: [<task_code>] # Array of tasks to be called, does not pass any input
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
                    service: '@CleverAge\ProcessBundle\Task\Doctrine\DoctrineReaderTask'
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
