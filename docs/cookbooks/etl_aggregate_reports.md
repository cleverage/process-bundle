ETL report aggregation
======================

This example shows an ETL path that reads several JSON stream log files (one JSON object per line), aggregates the
calls per service, and writes a CSV summary that can feed a dashboard.

Each line of the log files looks like:

```json
{"service": "catalog", "duration": 120, "status": 200, "date": "2024-01-01T10:00:00+00:00"}
```

```yaml
clever_age_process:
    configurations:
        app.etl_report_aggregate:
            description: 'Aggregate API logs per service'
            tasks:
                list_sources:
                    service: '@CleverAge\ProcessBundle\Task\File\FolderBrowserTask'
                    options:
                        folder_path: '%kernel.project_dir%/var/logs/api'
                        name_pattern: '*.json-stream'
                    outputs: [read_log]

                read_log:
                    service: '@CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamReaderTask' # Reads the file path given as input
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
                    outputs: [group_by_service]

                group_by_service:
                    service: '@CleverAge\ProcessBundle\Task\RowAggregatorTask'
                    options:
                        aggregate_by: service
                        aggregate_columns: [duration, status]
                        aggregation_key: calls
                    outputs: [iterate_services]

                iterate_services:
                    service: '@CleverAge\ProcessBundle\Task\InputIteratorTask'
                    outputs: [compute_stats]

                compute_stats:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    service:
                                        code: '[service]'
                                    calls:
                                        code: '[calls]'
                                        transformers:
                                            callback:
                                                callback: count
                                    total_duration:
                                        code: '[calls]'
                                        transformers:
                                            callback#1:
                                                callback: array_column
                                                right_parameters: [duration]
                                            callback#2:
                                                callback: array_sum
                    outputs: [write_summary]

                write_summary:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
                    options:
                        file_path: '%kernel.project_dir%/var/exports/report_summary_{date}.csv'
                        headers: [service, calls, total_duration]
```

How it works:
- [FolderBrowserTask](../reference/tasks/folder_browser_task.md) iterates over the files of the folder matching
  `name_pattern`, and outputs their path.
- [JsonStreamReaderTask](../reference/tasks/json_stream_reader_task.md) opens the file given as input and iterates over
  its lines, each one decoded as an array.
- [RowAggregatorTask](../reference/tasks/row_aggregator_task.md) is blocking: it groups the rows by `service`, storing
  the `duration` and `status` of each call under the `calls` key, and outputs the list of groups once all files have
  been read. For big volumes, keep the aggregated columns to the strict minimum, since everything is kept in memory.
- [InputIteratorTask](../reference/tasks/input_iterator_task.md) iterates over the groups, and the
  [TransformerTask](../reference/tasks/transformer_task.md) computes the number of calls and the total duration with
  [callback transformers](../reference/transformers/callback_transformer.md) (the `#1`/`#2` suffixes allow using the
  same transformer twice, see [TransformerTrait](../reference/traits/transformer_trait.md)).
- [CsvWriterTask](../reference/tasks/csv_writer_task.md) writes one line per service.
