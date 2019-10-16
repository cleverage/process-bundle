build:
	docker build -t cleverage_process:test .

test:
	docker run -it --mount type=bind,src=$$(pwd),dst=/src-cleverage_process cleverage_process:test php vendor/bin/phpunit
