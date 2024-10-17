.ONESHELL:
SHELL := /bin/bash

DOCKER_RUN_PHP = docker compose -f .docker/compose.yaml run --rm php "bash" "-c"
DOCKER_COMPOSE = docker compose -f .docker/compose.yaml

start: upd #[Global] Start application

src/vendor: #[Composer] install dependencies
	$(DOCKER_RUN_PHP) "composer install --no-interaction"

upd: #[Docker] Start containers detached
	touch .docker/.env
	make src/vendor
	$(DOCKER_COMPOSE) up --remove-orphans --detach

up: #[Docker] Start containers
	touch .docker/.env
	make src/vendor
	$(DOCKER_COMPOSE) up --remove-orphans

stop: #[Docker] Down containers
	$(DOCKER_COMPOSE) stop

down: #[Docker] Down containers
	$(DOCKER_COMPOSE) down

build: #[Docker] Build containers
	$(DOCKER_COMPOSE) build

ps: # [Docker] Show running containers
	$(DOCKER_COMPOSE) ps

bash: #[Docker] Connect to php container with current host user
	$(DOCKER_COMPOSE) exec php bash

logs: #[Docker] Show logs
	$(DOCKER_COMPOSE) logs -f

phpstan: #[Quality] Run PHPStan
	$(DOCKER_RUN_PHP) "vendor/bin/phpstan --no-progress --memory-limit=1G analyse"

php-cs-fixer: #[Quality] Run PHP-CS-Fixer
	$(DOCKER_RUN_PHP) "vendor/bin/php-cs-fixer fix --diff --dry-run --verbose"

rector: #[Quality] Run Rector
	$(DOCKER_RUN_PHP) "vendor/bin/rector"

phpunit: #[Tests] Run PHPUnit
	$(DOCKER_RUN_PHP) "vendor/bin/phpunit"
