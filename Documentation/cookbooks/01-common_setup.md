Common Setup
============

Bundle & task declaration
-------------------------

Optional step: create a bundle dedicated to the process bundle

Example of a generic declaration for all tasks:
```yaml
services:
    <Vendor>\ProcessBundle\Task\:
        resource: '../../../Task/*'
        autowire: true
        public: true
        shared: false
        tags:
            - { name: monolog.logger, channel: cleverage_process_task }
```

Configuration
-------------

Process are mostly defined in a `app/config/process` subfolder. You should replicate process codes depending on folder
layout (`my.feature.process` should be in `my/feature/process.yml`).

Private subprocess could be defined in the same file than their parents, only if parents are only wrappers.

Logging
-------

A simple default configuration, with rotating file, would be

```yaml
monolog:
    handlers:
        cdm_process:
            type: rotating_file
            path:  '%kernel.logs_dir%/cdm_process-%kernel.environment%.log'
            max_files: 10
            channels: ['cleverage_process']
        cdm_tasks:
            type: rotating_file
            path:  '%kernel.logs_dir%/cdm_tasks-%kernel.environment%.log'
            max_files: 10
            channels: ['cleverage_process_task', 'cleverage_process_transformer']
```
