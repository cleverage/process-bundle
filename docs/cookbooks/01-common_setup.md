Common Setup
============

Task & transformer declaration
------------------------------

Example of a generic declaration for all the tasks and transformers of your application:

```yaml
# config/services.yaml
services:
    App\Task\:
        resource: '../src/Task/*'
        autowire: true
        public: true
        shared: false
        tags:
            - { name: monolog.logger, channel: cleverage_process_task }

    App\Transformer\:
        resource: '../src/Transformer/*'
        autowire: true
        public: false
        tags:
            - { name: cleverage.transformer }
            - { name: monolog.logger, channel: cleverage_process_transformer }
```

If the file also contains the default `App\:` resource (which includes `src/Task/` and `src/Transformer/`), declare
these resources after it: the last definition of a service wins.

Alternatively, you can use `_instanceof` rules to configure every class implementing
`CleverAge\ProcessBundle\Model\TaskInterface` or `CleverAge\ProcessBundle\Transformer\TransformerInterface`, wherever
it is located in your sources (`_instanceof` only applies to the services defined in the same file, typically by the
`App\:` resource of `config/services.yaml`):

```yaml
services:
    _instanceof:
        CleverAge\ProcessBundle\Model\TaskInterface:
            public: true
            shared: false
            tags:
                - { name: monolog.logger, channel: cleverage_process_task }
        CleverAge\ProcessBundle\Transformer\TransformerInterface:
            tags:
                - { name: cleverage.transformer }
                - { name: monolog.logger, channel: cleverage_process_transformer }
```

Configuration
-------------

Processes are usually defined in a `config/packages/process/` subfolder, imported from the bundle configuration file:

```yaml
# config/packages/clever_age_process.yaml
imports:
    - { resource: process/ }

clever_age_process:
    default_error_strategy: stop
```

You should replicate process codes in the folder layout (`my.feature.process` should be in
`config/packages/process/my/feature/process.yaml`).

Private subprocesses could be defined in the same file as their parents, only if parents are only wrappers.

Logging
-------

A simple default configuration, with rotating files, would be:

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        process:
            type: rotating_file
            path: '%kernel.logs_dir%/process-%kernel.environment%.log'
            max_files: 10
            channels: ['cleverage_process']
        process_tasks:
            type: rotating_file
            path: '%kernel.logs_dir%/process_tasks-%kernel.environment%.log'
            max_files: 10
            channels: ['cleverage_process_task', 'cleverage_process_transformer']
```

Remember to exclude those channels from your main handlers if you don't want them to be logged twice (e.g.
`channels: ['!cleverage_process', '!cleverage_process_task', '!cleverage_process_transformer']`).

Example: lightweight file import
--------------------------------

Here is a minimal CSV-to-CSV workflow built only with tasks shipped with the bundle:
[CsvReaderTask](../reference/tasks/csv_reader_task.md) (iterable, reads one line at a time),
[TransformerTask](../reference/tasks/transformer_task.md) and [CsvWriterTask](../reference/tasks/csv_writer_task.md)
(blocking, writes each line and outputs the file path at the end).

```yaml
# config/packages/process/app/file_import.yaml
clever_age_process:
    configurations:
        app.file_import:
            description: 'Prepare the products CSV file'
            help: 'bin/console cleverage:process:execute app.file_import -c "file:''products.csv''"'
            tasks:
                read_csv:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask'
                    options:
                        file_path: '%kernel.project_dir%/var/data/{{ file }}'
                        delimiter: ';'
                    outputs: [transform]

                transform:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    error_strategy: skip
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    id:
                                        code: '[id]'
                                    slug:
                                        code:
                                            - '[name]'
                                            - '[category]'
                                        transformers:
                                            implode:
                                                separator: ' '
                                            slugify:
                                                separator: '-'
                    outputs: [write_csv]

                write_csv:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
                    options:
                        file_path: '%kernel.project_dir%/var/output/products_prepared_{date_time}.csv'
                        headers: [id, slug]
```

The CSV reader uses the first line of the file as headers (when the `headers` option is not set) and outputs each line
as an associative array. Lines that cannot be transformed are skipped (and logged) thanks to `error_strategy: skip`,
while any other error stops the process.
