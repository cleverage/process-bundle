Release v3.2
============

v3.2-dev
------

### Features

_Nothing yet_

### Fixes

_Nothing yet_

### BC breaks

_Nothing yet_

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
