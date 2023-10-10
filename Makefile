.ONESHELL:
SHELL := /bin/bash

test:
	php -dxdebug.mode=coverage vendor/bin/phpunit --coverage-html coverage-report

linter: #[Linter]
	vendor/bin/php-cs-fixer fix

phpstan: #[Phpstan]
	vendor/bin/phpstan
