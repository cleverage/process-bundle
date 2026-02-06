ETL report aggregation
======================

This example shows an ETL path that reads several JSON log files, aggregates rates per service, and writes a CSV summary that can feed a dashboard.

```yaml
clever_age_process:
    configurations:
        app.etl_report_aggregate:
            default_error_strategy: stop
            tasks:
                list_sources:
                    service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
                    options:
                        folder: '%kernel.project_dir%/data/logs'
                        filter: '*.json'
                    outputs: [read_log]

                read_log:
                    service: '@CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamReaderTask'
                    options:
                        file_path: '[file]'   # value injected from FolderBrowserTask
                        iterator: items
                    outputs: [map_log]

                map_log:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    service: { code: '[service]' }
                                    duration: { code: '[duration]' }
                                    status: { code: '[status]' }
                    outputs: [group_reports]

                group_reports:
                    service: '@CleverAge\ProcessBundle\Task\GroupByAggregateIterableTask'
                    options:
                        group_by: service
                        aggregate:
                            duration: { type: 'avg' }
                    outputs: [write_summary]

                write_summary:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
                    options:
                        file_path: '%kernel.project_dir%/var/exports/report_summary.csv'
                        headers: [service, duration]
```

The `FolderBrowserTask`, `JsonStreamReaderTask`, `TransformerTask`, and `GroupByAggregateIterableTask` classes live under `src/Task/File` and `src/Task`. They demonstrate a reusable read/transform/aggregate path for any automation workflow.
