Changelog v3.0 => v3.1
======================

Features
--------

* [GITHUB-83](https://github.com/cleverage/process-bundle/issues/83): added [events](../04-advanced_workflow.md#events) 
around process execution
* [GITHUB-86](https://github.com/cleverage/process-bundle/issues/86): added XML manipulation tools

Fixes
-----

BC breaks
---------

* [GIHTUB-82](https://github.com/cleverage/process-bundle/issues/82): the `default_error_strategy` is now mandatory. 
If you have any doubt, you can use `default_error_strategy: skip` to keep previous behavior. 
