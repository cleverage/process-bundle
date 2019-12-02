Contributing
============

Every contributions are welcomed. This bundle aims to provide a standalone set of generic component. 
If a contribution is too specific or requires dependencies, it might be put in a separated sub-bundle.

Ideally every PR should contain documentation and unit test updates.

## Deprecations notices

When a feature should be deprecated, or when you have a breaking change for a future version, please :
* [Fill an issue](https://github.com/cleverage/process-bundle/issues/new)
* Add TODO comments with the following format: `@TODO deprecated v4.0`
* Trigger a deprecation error: `@trigger_error('This feature will be deprecated in v4.0', E_USER_DEPRECATED);`
