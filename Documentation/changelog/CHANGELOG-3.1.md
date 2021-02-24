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
