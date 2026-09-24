File synchronization ETL
========================

This recipe describes a typical ETL flow: read a CSV file, reject invalid lines, normalize the data, remove duplicates,
then write the result to another CSV file while logging some statistics.

```yaml
clever_age_process:
    configurations:
        app.etl_file_sync:
            description: 'Normalize the catalog CSV file'
            tasks:
                read_source:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask'
                    options:
                        file_path: '%kernel.project_dir%/var/data/catalog.csv'
                        delimiter: ';'
                    outputs: [filter_valid]

                filter_valid:
                    service: '@CleverAge\ProcessBundle\Task\FilterTask'
                    options:
                        not_empty:
                            '[sku]': ~
                    outputs: [normalize]
                    error_outputs: [log_rejected] # Lines without sku

                log_rejected:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\LoggerTask'
                    options:
                        level: warning
                        message: 'Line without sku'

                normalize:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    error_strategy: skip # A line with an invalid date is logged and skipped
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    sku:
                                        code: '[sku]'
                                    price:
                                        code: '[price]'
                                        transformers:
                                            cast:
                                                type: float
                                    updated_at:
                                        code: '[updated_at]'
                                        transformers:
                                            date_parser:
                                                format: 'd/m/Y H:i'
                                            date_format:
                                                format: 'Y-m-d'
                    outputs: [deduplicate, count_rows]

                count_rows:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\StatCounterTask'

                deduplicate:
                    service: '@CleverAge\ProcessBundle\Task\GroupByAggregateIterableTask'
                    options:
                        group_by_accessors: ['[sku]'] # The last line of each sku is kept
                    outputs: [iterate]

                iterate:
                    service: '@CleverAge\ProcessBundle\Task\InputIteratorTask'
                    outputs: [write_target]

                write_target:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
                    options:
                        file_path: '%kernel.project_dir%/var/exports/catalog_normalized_{date}.csv'
                        headers: [sku, price, updated_at]
```

How it works:
- [CsvReaderTask](../reference/tasks/csv_reader_task.md) is iterable: each line goes through the following tasks
  before the next one is read.
- [FilterTask](../reference/tasks/filter_task.md) skips lines with an empty `sku` and sends them to its error branch,
  where the [LoggerTask](../reference/tasks/logger_task.md) logs them.
- The [TransformerTask](../reference/tasks/transformer_task.md) casts the price and reformats the date (see the
  [mapping](../reference/transformers/mapping_transformer.md),
  [date_parser](../reference/transformers/date_parser_transformer.md) and
  [date_format](../reference/transformers/date_format_transformer.md) transformers).
- [StatCounterTask](../reference/tasks/stat_counter_task.md) counts the normalized lines and logs the total at the
  end of the process.
- [GroupByAggregateIterableTask](../reference/tasks/group_by_aggregate_iterable_task.md) is blocking: it keeps the
  last line of each `sku` and outputs them all at once when the file has been fully read. The
  [InputIteratorTask](../reference/tasks/input_iterator_task.md) then iterates again over this array, one line at a
  time, to the [CsvWriterTask](../reference/tasks/csv_writer_task.md).

Note that deduplication requires keeping one line per `sku` in memory: for very big files, prefer deduplicating at the
destination (e.g. with a database unique key).
