Release v3.1
============

v3.1-dev
------

### Features

### Fixes

* FolderBrowserTask: Accept array type for `name_pattern` option

### BC breaks

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
