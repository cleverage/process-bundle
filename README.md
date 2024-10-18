CleverAge/ProcessBundle
=======================

## Introduction

This bundle allows to configure series of tasks to be performed on a certain order.
Basically, it will greatly ease the configuration of import and exports but can do much more.

Compatible with [Symfony stable version and latest Long-Term Support (LTS) release](https://symfony.com/releases).

Demo project can be found on [there](https://github.com/cleverage/process-bundle-ui-demo).

## Documentation

- [Quick start](doc/01-quick_start.md)
- [Task types](doc/02-task_types.md)
- [Custom tasks and development](doc/03-custom_tasks.md)
- [Advanced workflow](doc/04-advanced_workflow.md)
- Cookbooks
  - [Common Setup](doc/cookbooks/01-common_setup.md)
  - [Transformations]
  - [Flow manipulation]
  - [Dummy tasks]
  - [Debugging]
  - [Logging]
  - [Subprocess]
  - [File manipulation]
  - [Direct call (in controller)]
  - [Performances monitoring](doc/cookbooks/performances_monitoring.md)
  - [Memory usage analysis](doc/cookbooks/memory_usage_graph.md)
- Reference
  - [Process definition](doc/reference/01-process_definition.md)
  - [Task definition](doc/reference/02-task_definition.md)
  - Basic and debug
    - [ConstantOutputTask](doc/reference/tasks/constant_output_task.md)
    - [ConstantIterableOutputTask](doc/reference/tasks/constant_iterable_output_task.md)
    - [CounterTask]
    - [DebugTask](doc/reference/tasks/debug_task.md)
    - [DieTask](doc/reference/tasks/die_task.md)
    - [DummyTask](doc/reference/tasks/dummy_task.md)
    - [ErrorForwarderTask]
    - [EventDispatcherTask](doc/reference/tasks/event_dispatcher_task.md)
    - [MemInfoDumpTask]
    - [StopwatchTask]
  - Data manipulation and transformations
    - [DenormalizerTask](doc/reference/tasks/denormalizer_task.md)
    - [NormalizerTask](doc/reference/tasks/normalizer_task.md)
    - [DeserializerTask]
    - [SerializerTask]
    - [PropertyGetterTask](doc/reference/tasks/property_getter_task.md)
    - [PropertySetterTask](doc/reference/tasks/property_setter_task.md)
    - [ObjectUpdaterTask]
    - [SplitJoinLineTask]
    - [TransformerTask](doc/reference/tasks/transformer_task.md)
    - [ValidatorTask]
  - File/CSV
    - [CsvReaderTask](doc/reference/tasks/csv_reader_task.md)
    - [CsvWriterTask](doc/reference/tasks/csv_writer_task.md)
    - [CSVSplitterTask]
    - [InputCsvReaderTask]
  - File/JsonStream
    - [JsonStreamReaderTask]
  - File/XML
    - [XmlReaderTask](doc/reference/tasks/xml_reader_task.md)
    - [XmlWriterTask](doc/reference/tasks/xml_writer_task.md)
  - File/Yaml
    - [YamlReaderTask]
    - [YamlWriterTask]
  - File
    - [FileMoverTask]
    - [FileReaderTask]
    - [FileRemoverTask]
    - [FileWriterTask]
    - [FolderBrowserTask]
    - [InputFolderBrowserTask]
  - Flow manipulation
    - [AggregateIterableTask](doc/reference/tasks/aggregate_iterable_task.md)
    - [InputAggregatorTask](doc/reference/tasks/input_aggregator_task.md)
    - [InputIteratorTask](doc/reference/tasks/input_iterator_task.md)
    - [ArrayMergeTask]
    - [ColumnAggregatorTask]
    - [RowAggregatorTask]
    - [FilterTask]
    - [GroupByAggregateIterableTask]
    - [SimpleBatchTask]
    - [IterableBatchTask]
    - [SkipEmptyTask]
    - [StopTask]
  - Process
    - [CommandRunnerTask]
    - [ProcessExecutorTask]
    - [ProcessLauncherTask]
  - Reporting
    - [AdvancedStatCounterTask]
    - [LoggerTask]
    - [StatCounterTask]
  - Transformers
    - Basic and debug
      - [CachedTransformer]
      - [CallbackTransformer]
      - [CastTransformer]
      - [ConstantTransformer]
      - [ConvertValueTransformer]
      - [DebugTransformer]
      - [DefaultTransformer]
      - [GenericTransformer]
      - [EvaluatorTransformer]
      - [ExpressionLanguageMapTransformer]
      - [MappingTransformer](doc/reference/transformers/mapping_transformer.md)
      - [MultiReplaceTransformer]
      - [PregFilterTransformer]
      - [RulesTransformer](doc/reference/transformers/rules_transformer.md)
      - [TypeSetterTransformer]
      - [UnsetTransformer]
      - [WrapperTransformer]
    - Array
      - [ArrayElementTransformer]
      - [ArrayFilterTransformer](doc/reference/transformers/array_filter_transformer.md)
      - [ArrayFirstTransformer]
      - [ArrayLastTransformer]
      - [ArrayMapTransformer]
      - [ArrayUnsetTransformer]
    - Date
      - [DateFormatTransformer](doc/reference/transformers/date_format.md)
      - [DateParserTransformer](doc/reference/transformers/date_parser.md)
    - Object
      - [InstantiateTransformer]
      - [PropertyAccessorTransformer]
      - [RecursivePropertySetterTransformer]
    - Serialization
      - [DenormalizeTransformer]
      - [NormalizeTransformer]
    - String
      - [ExplodeTransformer]
      - [HashTransformer]
      - [ImplodeTransformer]
      - [SlugifyTransformer]
      - [SprintfTransformer]
      - [TrimTransformer]
    - XML
      - [XpathEvaluatorTransformer](doc/reference/transformers/xpath_evaluator.md)
  - Other bridges
    - [Doctrine](https://github.com/cleverage/doctrine-process-bundle)
    - [Eav](https://github.com/cleverage/eav-process-bundle)
    - [Soap](https://github.com/cleverage/soap-process-bundle)
    - [Another Soap](https://github.com/cleverage/process-soap-bundle)
    - [Rest](https://github.com/cleverage/rest-process-bundle)
    - [Enqueue](https://github.com/cleverage/enqueue-process-bundle)
    - [Flysystem](https://github.com/cleverage/flysystem-process-bundle)
    - [Cache](https://github.com/cleverage/cache-process-bundle)
  - [Generic transformers definition](doc/reference/03-generic_transformers_definition.md)
- [UI](https://github.com/cleverage/processuibundle)

## Support & Contribution

For general support and questions, please use [Github](https://github.com/cleverage/process-bundle/issues).
If you think you found a bug or you have a feature idea to propose, feel free to open an issue after looking at the [contributing](CONTRIBUTING.md) guide.

## License

This bundle is under the MIT license.
For the whole copyright, see the [LICENSE](LICENSE) file distributed with this source code.
