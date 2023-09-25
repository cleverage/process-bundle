CleverAge/ProcessBundle
=======================

## Introduction

This bundle allows to configure series of tasks to be performed on a certain order.
Basically, it will greatly ease the configuration of import and exports but can do much more.

Compatible with every [currently supported Symfony versions](https://symfony.com/releases).

## Index

- [Quick start](doc/01-quick_start.md)
- [Task types](doc/02-task_types.md)
- [Custom tasks and development](doc/03-custom_tasks.md)
- [Advanced workflow](doc/04-advanced_workflow.md)
- [Contribute](CONTRIBUTING.md)
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
        - [DebugTask](doc/reference/tasks/debug_task.md)
        - [DummyTask](doc/reference/tasks/dummy_task.md)
        - [EventDispatcherTask](doc/reference/tasks/event_dispatcher_task.md)
      - Data manipulation and transformations
        - [DenormalizerTask](doc/reference/tasks/denormalizer_task.md)
        - [NormalizerTask](doc/reference/tasks/normalizer_task.md)
        - [PropertyGetterTask](doc/reference/tasks/property_getter_task.md)
        - [PropertySetterTask](doc/reference/tasks/property_setter_task.md)
        - [TransformerTask](doc/reference/tasks/transformer_task.md)
      - File/CSV
        - [CsvReaderTask](doc/reference/tasks/csv_reader_task.md)
        - [CsvWriterTask](doc/reference/tasks/csv_writer_task.md)
      - File/XML
        - [XmlReaderTask](doc/reference/tasks/xml_reader_task.md)
        - [XmlWriterTask](doc/reference/tasks/xml_writer_task.md)
      - Flow manipulation
        - [AggregateIterableTask](doc/reference/tasks/aggregate_iterable_task.md)
        - [InputAggregatorTask](doc/reference/tasks/input_aggregator_task.md)
        - [InputIteratorTask](doc/reference/tasks/input_iterator_task.md)
    - Transformers
        - [ArrayFilterTransformer](doc/reference/transformers/array_filter_transformer.md)
        - [MappingTransformer](doc/reference/transformers/mapping_transformer.md)
        - [RulesTransformer](doc/reference/transformers/rules_transformer.md)
        - [DateFormatTransformer](doc/reference/transformers/date_format.md)
        - [DateParserTransformer](doc/reference/transformers/date_parser.md)
        - [XpathEvaluatorTransformer](doc/reference/transformers/xpath_evaluator.md)
    - [Generic transformers definition](doc/reference/03-generic_transformers_definition.md)
