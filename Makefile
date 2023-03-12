# Include .env.dist for the default values
include .env.dist

# Include .env only if it exists
ifneq ("",$(wildcard $(.env)))
include .env
endif

# Default image to use for tests
SF_ENV=sf5
LOCAL_DOCKER_TAG=cleverage_process:test
DOCKER_RUN=docker run -it --rm \
	--mount type=bind,src=$$(pwd),dst=/src-cleverage_process

pull: pull/$(SF_ENV)

pull/sf5:
	docker pull cleverage/process-bundle:sf5

build/local:
	docker build -t cleverage_process:test .

build/%:
	DOCKER_TAG=$(@F) DOCKERFILE_PATH=Dockerfile IMAGE_NAME=cleverage/process-bundle:$(@F) ./hooks/build

shell: shell/$(SF_ENV)

shell/local:
	$(DOCKER_RUN) $(LOCAL_DOCKER_TAG) bash

shell/%:
	$(DOCKER_RUN) cleverage/process-bundle:$(@F) bash

test: test/$(SF_ENV)

test/local:
	$(DOCKER_RUN) $(LOCAL_DOCKER_TAG) ./bin/console c:c
	$(DOCKER_RUN) $(LOCAL_DOCKER_TAG) php vendor/bin/phpunit

test/%:
	$(DOCKER_RUN) cleverage/process-bundle:$(@F) ./bin/console c:c
	$(DOCKER_RUN) cleverage/process-bundle:$(@F) php vendor/bin/phpunit

bench: bench/$(SF_ENV)

bench/local:
	$(DOCKER_RUN) $(LOCAL_DOCKER_TAG) /bin/bash -c \
			"./bin/console --env=test c:c; \
			blackfire run ./bin/console --env=test c:p:e test.long_process -vvv"

bench/%:
	$(DOCKER_RUN) cleverage/process-bundle:$(@F) /bin/bash -c \
			"./bin/console --env=test c:c; \
			blackfire run ./bin/console --env=test c:p:e test.long_process -vvv"

vendor: vendor/$(SF_ENV)

vendor/%:
	rm -rf vendor-$(@F) || true
	docker container create --name cleverage_process_bundle_tmp cleverage/process-bundle:$(@F)
	docker cp cleverage_process_bundle_tmp:/app/vendor vendor-$(@F)
	docker container rm cleverage_process_bundle_tmp

linter:
	$(DOCKER_RUN) $(LOCAL_DOCKER_TAG) /bin/bash -c "vendor/bin/phpstan"