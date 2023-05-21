# Include .env.dist for the default values
include .env.dist

# Include .env only if it exists
ifneq ("",$(wildcard $(.env)))
include .env
endif

test:
	php -dxdebug.mode=coverage vendor/bin/phpunit --coverage-html coverage-report

linter:
	vendor/bin/rector process
	vendor/bin/ecs check --fix
	vendor/bin/phpstan
