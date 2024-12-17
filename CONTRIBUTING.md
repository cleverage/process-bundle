Contributing
============

First of all, **thank you** for contributing, **you are awesome**!

Here are a few rules to follow in order to ease code reviews, and discussions before
maintainers accept and merge your work.

You MUST run the quality & test suites.

You SHOULD write (or update) unit tests.

You SHOULD write documentation.

Please, write [commit messages that make sense](https://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html),
and [rebase your branch](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) before submitting your Pull Request.

One may ask you to [squash your commits](https://gitready.com/advanced/2009/02/10/squashing-commits-with-rebase.html)
too. This is used to "clean" your Pull Request before merging it (we don't want
commits such as `fix tests`, `fix 2`, `fix 3`, etc.).

Thank you!

## Running the quality & test suites

Tests suite uses Docker environments in order to be idempotent to OS's. More than this
PHP version is written inside the Dockerfile; this assures to test the bundle with
the same resources. No need to have PHP installed.

You only need Docker set it up.

To allow testing environments more smooth we implemented **Makefile**.
You have two commands available:

```bash
make quality
```

```bash
make tests
```

## Deprecations notices

When a feature should be deprecated, or when you have a breaking change for a future version, please :
* [Fill an issue](https://github.com/cleverage/process-bundle/issues/new)
* Add TODO comments with the following format: `@TODO deprecated v4.0`
* Trigger a deprecation error: `@trigger_error('This feature will be deprecated in v4.0', E_USER_DEPRECATED);`

You can check which deprecation notice is triggered in tests
* `make bash`
* `SYMFONY_DEPRECATIONS_HELPER=0 ./vendor/bin/phpunit`
