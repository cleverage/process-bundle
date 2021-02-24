CleverAge/ProcessBundle
=======================

## Introduction

This bundle allows to configure series of tasks to be performed on a certain order.
Basically, it will greatly ease the configuration of import and exports but can do much more.

Compatible with every [currently supported Symfony versions](https://symfony.com/releases).

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
    - [Performances monitoring](Documentation/cookbooks/performances_monitoring.md)
    - [Memory usage analysis](Documentation/cookbooks/memory_usage_graph.md)
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
        - [XmlWriterTask](Documentation/reference/tasks/xml_writer_task.md)
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
- Changelog
    - [v3.2](Documentation/changelog/CHANGELOG-3.2.md)
    - [v3.1](Documentation/changelog/CHANGELOG-3.1.md)
    - [Older versions](Documentation/changelog/CHANGELOG-2.0-1.1.md)
