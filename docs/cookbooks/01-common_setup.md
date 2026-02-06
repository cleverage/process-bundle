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

Example: lightweight file import
--------------------------------

To give more context on how configuration ties to the PHP code, here is a minimal file-import workflow built with classes already shipped in `src/Task/File` and `src/Task/Reporting`.

```yaml
clever_age_process:
    configurations:
        app.file_import:
            default_error_strategy: stop
            tasks:
                read_csv:
                    service: '@CleverAge\ProcessBundle\Task\File\InputFileReaderTask'
                    options:
                        file_path: '%kernel.project_dir%/data/products.csv'
                        format: csv
                    outputs: [split_rows]

                split_rows:
                    service: '@CleverAge\ProcessBundle\Task/File/Csv/CsvSplitterTask'
                    options:
                        delimiter: ';'
                    outputs: [transform]

                transform:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    id: { code: '[id]' }
                                    slug:
                                        code:
                                            - '[name]'
                                            - '[category]'
                                        transformers:
                                            implode:
                                                separator: '-'
                    outputs: [write_csv]

                write_csv:
                    service: '@CleverAge\ProcessBundle\Task\File/Csv/CsvWriterTask'
                    options:
                        file_path: '%kernel.project_dir%/var/output/products_prepared.csv'
                        headers:
                            - id
                            - slug
```

Each task above maps to the concrete classes such as `InputFileReaderTask`, `CsvSplitterTask` and `CsvWriterTask` found under `src/Task/File`. Mentioning the `default_error_strategy` helps downstream code in `src/Configuration/ProcessConfiguration.php` know how to propagate failures.
