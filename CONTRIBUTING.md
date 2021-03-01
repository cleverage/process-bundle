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

You can check which deprecation notice is triggered in tests
* `make shell`
* `SYMFONY_DEPRECATIONS_HELPER=0 ./vendor/bin/phpunit`

## Task and transformer documentation

Please use the following normalized PHPDoc template on every task and transformer (remove every item not needed).

```php
/**
 * Task summary (one line)
 * 
 * Task description (multiple line, markdown, optional)
 *
 * ##### Task or Transformer reference
 *
 * * **Service**: `CleverAge\ProcessBundle\Path\To\Task\Class`
 * * **code**: `transformer_code`
 * * **Iterable task**
 * * **Blocking task**
 * * **Flushable task**
 * * **Input**: `type`, description
 * * **Output**: `type`, description
 * 
 * ##### Options
 *
 * * `code` (`type`, _required_, _defaults to_ `value`): description
 *
 * @example "Resources/examples/path/to/file.yaml" description
 *          
 * @author  Your Name <you@example.com>
 */
```

On public methods internal to the Process Bundle interfaces, please add `{@inheritDoc}` and `@internal`. 
This is to avoid useless cluttering in the class description page.
