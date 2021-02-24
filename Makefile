# Include .env.dist for the default values
include .env.dist

# Include .env only if it exists
ifneq ("",$(wildcard $(.env)))
include .env
endif

# Default image to use for tests
SF_ENV=sf4
LOCAL_DOCKER_TAG=cleverage_process:test
DOCKER_RUN=docker run -it --rm \
	-e BLACKFIRE_CLIENT_ID=$(BLACKFIRE_CLIENT_ID) \
	-e BLACKFIRE_CLIENT_TOKEN=$(BLACKFIRE_CLIENT_TOKEN) \
	--mount type=bind,src=$$(pwd),dst=/src-cleverage_process

pull: pull/$(SF_ENV)

pull/sf3:
	docker pull cleverage/process-bundle:sf3

pull/sf4:
	docker pull cleverage/process-bundle:sf4

pull/sf5:
	docker pull cleverage/process-bundle:sf5

build/local:
	docker build -t cleverage_process:test .

build/%:
	DOCKER_TAG=$(@F) DOCKERFILE_PATH=Dockerfile IMAGE_NAME=cleverage_process:$(@F)

shell: shell/$(SF_ENV)

shell/local: build/local
	$(DOCKER_RUN) $(LOCAL_DOCKER_TAG) bash

shell/%: pull/%
	$(DOCKER_RUN) cleverage/process-bundle:$(@F) bash

test: test/$(SF_ENV)

test/local: build/local
	$(DOCKER_RUN) $(LOCAL_DOCKER_TAG) php vendor/bin/phpunit

test/%: pull/%
	$(DOCKER_RUN) cleverage/process-bundle:$(@F) php vendor/bin/phpunit

bench: bench/$(SF_ENV)

bench/local: build/local
	$(DOCKER_RUN) $(LOCAL_DOCKER_TAG) /bin/bash -c \
			"./bin/console --env=test c:c; \
			blackfire run ./bin/console --env=test c:p:e test.long_process -vvv"

bench/%: pull/%
	$(DOCKER_RUN) cleverage/process-bundle:$(@F) /bin/bash -c \
			"./bin/console --env=test c:c; \
			blackfire run ./bin/console --env=test c:p:e test.long_process -vvv"
