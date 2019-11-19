test:
	docker run -it --mount type=bind,src=$$(pwd),dst=/src-cleverage_process cleverage/process-bundle:sf4 php vendor/bin/phpunit
