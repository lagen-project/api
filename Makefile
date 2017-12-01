install:
	composer install

test:
	mkdir -p tests/data
	php bin/console doctrine:database:create --env=test
	php bin/console doctrine:schema:update --force --env=test
	./vendor/bin/behat --format=progress
