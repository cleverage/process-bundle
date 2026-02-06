File synchronization ETL
=======================

This recipe describes a typical ETL flow: read a CSV file, enrich the data with transformations, then write to another file while keeping a statistics log.

```yaml
clever_age_process:
    configurations:
        app.etl_file_sync:
            default_error_strategy: stop
            tasks:
                load_source:
                    service: '@CleverAge\ProcessBundle\Task\File\InputFileReaderTask'
                    options:
                        file_path: '%kernel.project_dir%/data/catalog.csv'
                        format: csv
                    outputs: [split_rows]

                split_rows:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvSplitterTask'
                    options:
                        delimiter: ';'
                    outputs: [normalize]

                normalize:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    sku: { code: '[sku]' }
                                    price: { code: '[price]', transformers: { cast: { type: float } } }
                                    sent_at:
                                        code: 'format_datetime("[updated_at]", "Y-m-d")'
                    outputs: [deduplicate]

                deduplicate:
                    service: '@CleverAge\ProcessBundle\Task\FilterTask'
                    options:
                        unique_field: sku
                    outputs: [write_target]

                write_target:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
                    options:
                        file_path: '%kernel.project_dir%/var/exports/catalog_normalized.csv'
                        headers: [sku,price,sent_at]
                    outputs: [log_stats]

                log_stats:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\StatCounterTask'
                    options:
                        increment:
                            rows: 1
```

The `FilterTask` in the sequence corresponds to `src/Task/FilterTask.php`, and the CSV tasks come from `src/Task/File/Csv`. The final counter uses `src/Task/Reporting/StatCounterTask.php` to show how to append a simple metric to the logs.
