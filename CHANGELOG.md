v4.4
-----

### Add 
* [#168](https://github.com/cleverage/process-bundle/issues/168) Add JsonStreamWriterTask with related doc
* [#172](https://github.com/cleverage/process-bundle/issues/172) Add spl_file_object_flags and json_flags options on JsonStream*Tasks. Update docs.
* [#174](https://github.com/cleverage/process-bundle/issues/174) Add FileSplitterTask using Filesystem/SplFile. Add doc.

### Fixes
* [#169](https://github.com/cleverage/process-bundle/issues/169) Fix JsonStreamFile empty line at the end issue, even if SKIP_EMPTY is set. Add JsonStreamReaderTask doc.

v4.3
-----

### Add 
* [#166](https://github.com/cleverage/process-bundle/issues/166) Add PregMatchTransformer

v4.2
-----

### Add
* [#cleverage/archive-process-bundle#1](https://github.com/cleverage/archive-process-bundle/issues/1) Add new bridge ArchiveProcessBundle
* [#162](https://github.com/cleverage/process-bundle/issues/162) Add timestamp placeholder on file_path parameter of CsvWriterTask. Improve documentation.
* [#164](https://github.com/cleverage/process-bundle/issues/164) Add cleverage/cache-process-bundle dependency

v4.1.1
-----

### Fixes

* [#158](https://github.com/cleverage/process-bundle/issues/158) Add dependency to symfony/service-contract

v4.1
-----

### Add
* [#155](https://github.com/cleverage/process-bundle/issues/155) Add InputFileReaderTask (Reads the whole input file and outputs its content), InputLineReaderTask (Reads an input file line by line and outputs each line.) and LineReaderTask (Reads a file line by line and outputs each line.)

v4.0
------

## BC breaks

* [#142](https://github.com/cleverage/process-bundle/issues/142) Remove FileFetchTask, use `cleverage/flysystem-process-bundle` instead.
* [#142](https://github.com/cleverage/process-bundle/issues/142) YamlReaderTask & YamlWriterTask namespaces changed to `CleverAge\ProcessBundle\Task\File\Yaml`
* [#142](https://github.com/cleverage/process-bundle/issues/142) Array***Transformers namespaces changed to `CleverAge\ProcessBundle\Transformer\Array`
* [#142](https://github.com/cleverage/process-bundle/issues/142) NormalizeTransformer & DenormalizeTransformer namespaces changed to `CleverAge\ProcessBundle\Transformer\Serialization`
* [#142](https://github.com/cleverage/process-bundle/issues/142) DateFormatTransformer & DateParserTransformer namespaces changed to `CleverAge\ProcessBundle\Transformer\Date`
* [#142](https://github.com/cleverage/process-bundle/issues/142) ExplodeTransformer, HashTransformer, ImplodeTransformer, SlugifyTransformer, SprintfTransformer & TrimTransformer namespaces changed to `CleverAge\ProcessBundle\Transformer\String`
* [#142](https://github.com/cleverage/process-bundle/issues/142) InstantiateTransformer, PropertyAccessorTransformer RecursivePropertySetterTransformer namespaces changed to `CleverAge\ProcessBundle\Transformer\Object`
* [#147](https://github.com/cleverage/process-bundle/issues/147) Replace `Symfony\Component\Form\Exception\InvalidConfigurationException` by `Symfony\Component\Config\Definition\Exception\InvalidConfigurationException`
* [#148](https://github.com/cleverage/process-bundle/issues/148) Update services (step 1) according to Symfony best practices. Services should not use autowiring or autoconfiguration. Instead, all services should be defined explicitly. 
Services must be prefixed with the bundle alias instead of using fully qualified class names => `cleverage_process`
* [#150](https://github.com/cleverage/process-bundle/issues/150) The class `\CleverAge\ProcessBundle\Task\Debug\MemInfoDumpTask` has been deleted without suggested replacement
* [#115](https://github.com/cleverage/process-bundle/issues/115) New mandatory configuration `default_error_strategy` on `clever_age_process` level. See [Quick Start/Global configuration](docs/01-quick_start.md#global-configuration)
### Changes

* [#139](https://github.com/cleverage/process-bundle/issues/139) Update Makefile & .docker for local standalone usage
* [#139](https://github.com/cleverage/process-bundle/issues/139) Update rector, phpstan & php-cs-fixer configurations & apply it
* [#141](https://github.com/cleverage/process-bundle/issues/141) `league/flysystem-bundle` is not required anymore
* [#130](https://github.com/cleverage/process-bundle/issues/130) EventDispatcherInterface service declaration breaks dependency injection
* [#147](https://github.com/cleverage/process-bundle/issues/147) Add missing dependencies on `symfony/dotenv` and `symfony/runtime`
* [#147](https://github.com/cleverage/process-bundle/issues/147) Remove dependencies on `symfony/form`, `symfony/messenger` & `symfony/scheduler`
* [#146](https://github.com/cleverage/process-bundle/issues/146) eav-process-bundle, enqueue-process-bundle, cache-process-bundle and process-soap-bundle were deprecated / archived.
* [#141](https://github.com/cleverage/process-bundle/issues/141) Add a default value to the node "default_error_strategy"

### Fixes

* [#129](https://github.com/cleverage/process-bundle/issues/129) Remove wrong replace configuration on composer.json. Add missing suggest
* Miscellaneous fixes, show full diff : https://github.com/cleverage/process-bundle/compare/v4.0.0-rc2...v4.0.0

v4.0-RC2
------

## BC breaks

* Bump php version to >=8.2
* Bump symfony version to ^6.4|^7.1

### Fixes

* Miscellaneous fixes, show full diff : https://github.com/cleverage/process-bundle/compare/v4.0.0-rc1...v4.0.0-rc2

v4.0-RC1
------

## BC breaks

* Bump php version to >=8.1
* Bump symfony version to ^6.3

## Changes
* Add some phpunit tests
* Apply Rector & Phpstan
* Add StopwatchTask
* Change directory structure. Move Symfony code to /src, documentation to /doc, and tests to /tests

### Fixes

* Miscellaneous fixes, show full diff : https://github.com/cleverage/process-bundle/compare/v3.2.9...v4.0.0-rc1

v3.2.9
------

### Fixes

https://github.com/cleverage/process-bundle/compare/v3.2.8...v3.2.9

v3.2.8
------

### Fixes

https://github.com/cleverage/process-bundle/compare/v3.2.7...v3.2.8

v3.2.7
------

### Fixes

Suppress deprecation message

v3.2.6
------

### Fixes

Fix SubprocessInstance Task for Symfony >=5

v3.2.5
------

### Fixes

Upgrade psr/cache

v3.2.4
------

### Features

* Added a `ttl` option in the `cached` transformer

v3.2.3
------

### Features

* Added `multi_replace` transformer
* Added `cached` transformer

### Fixes

* Fixed return value of list and help commands (mandatory for Symfony 5)

### BC breaks

* Added `psr/cache` as a dependency, but it shouldn't break anything
* Added `ext-intl` as a dependency, since required by the `slugify` transformer

v3.2.2
------

### Fixes

* Ignore empty lines in `\CleverAge\ProcessBundle\Filesystem\CsvResource::getLineCount`.
* Fixed `\CleverAge\ProcessBundle\Task\AbstractIterableOutputTask` skipping iterations when inside another iteration loop
* `\CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException` now displays the failing process code
* `\CleverAge\ProcessBundle\Transformer\TransformerTrait` now displays a more explicit message on wrong options type


v3.2.1
------

### Fixes

* Fatal error while loading configuration in Symfony 3.4

v3.2.0
------

### Features

* [GITHUB-121](https://github.com/cleverage/process-bundle/issues/121): Enable compatibility with Symfony 5
* [GITHUB-118](https://github.com/cleverage/process-bundle/pull/118): Added boilerplate code to avoid deprecations notices for event listeners

### BC breaks

There is no BC break for this version, but note that `sidus/base-bundle` has been removed from dependencies.
If you use it, it should already be inside your own composer.json.



Release v3.1
============

v3.1-dev
------

### Features

_Nothing yet_

### Fixes

_Nothing yet_

### BC breaks

_Nothing yet_

v3.1.5
------

### Fixes

* [GITHUB-120](https://github.com/cleverage/process-bundle/pull/120): FolderBrowserTask: Accept array type for `name_pattern` option


v3.1.4
------

### Features

* (_backport from v3.0.9_) Adding simple task to launch system commands


v3.1.3
------

### Features

* (_backport from v3.0.7_) Allowing ValidatorTask to output constraint violations with an option
* (_backport from v3.0.6_) Adding ArrayUnsetTransformer
* (_backport from v3.0.5_) Adding basic debug transformer

### Fixes

* (_backport from v3.0.8_) Fixing AbstractIterableOutputTask that was inconsistent when chained, refactoring InputIteratorTask that had the proper implementation with the AbstractIterableOutputTask as parent

v3.1.2
------

### Fixes

* Fixed bad static access in tests

v3.1.1
------

### Features

* (_backport from v3.0.4_) Adding simple file reader task and cast transformer
* (_backport from v3.0.3_) FilterTask now outputs skipped content to error output

### Fixes

* Removed useless, CPU intensive, log on CsvSplitterTask

v3.1.0
------

### Features

* [GITHUB-83](https://github.com/cleverage/process-bundle/issues/83): added [events](../04-advanced_workflow.md#events)
  around process execution
* [GITHUB-86](https://github.com/cleverage/process-bundle/issues/86): added XML manipulation tools
* [GITHUB-109](https://github.com/cleverage/process-bundle/issues/109): added an event during CLI process execution
* [GITHUB-107](https://github.com/cleverage/process-bundle/issues/107): allow to use directly a string in task `outputs`
  and `errors` configurations

### Fixes

* [GITHUB-99](https://github.com/cleverage/process-bundle/issues/99): transformer exception message improvements

### BC breaks

* [GIHTUB-82](https://github.com/cleverage/process-bundle/issues/82): the `default_error_strategy` is now mandatory.
  If you have any doubt, you can use `default_error_strategy: skip` to keep previous behavior.
* [GITHUB-106](https://github.com/cleverage/process-bundle/issues/106): an entry-point cannot have an ancestor anymore.
  The behaviour was undefined, and now it will throw an exception.


UPGRADE TO 2.0
==============

Task Logging
------------

Instead of using `CleverAge\ProcessBundle\Model\ProcessState::log` you must now use the standard
`Psr\Log\LoggerInterface` with the `cleverage_process_task` chanel. You should also pass
`CleverAge\ProcessBundle\Model\ProcessState::getLogContext` to the log context.

TransformerTask
---------------

The main option is now "transformers", which accept transformer codes and then transformer options.
Default options should now look like:
```yaml
options:
    transformers:
        mapping:
            mapping:
                <key>: <options>
```


UPGRADE TO 1.1
==============

MappingTransformer
------------------

* The option "ignore_extra" is renamed to "keep_input".

Other
-----

* Fixed issues with blocking tasks
* Removed deprecated methods
* added input/output in process manager (may allow a start_process_task)

New issues :
* Error workflow

Planned (v2+)
============

* automated transformer creation & refactoring
    * easy test cases via yml ?
* changes in interfaces
    * allow blocking + iterable
* FIFO queues for in/out
