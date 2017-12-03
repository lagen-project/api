install:
	mkdir config/jwt var/data
	openssl genrsa -out config/jwt/private.pem -aes256 4096
	openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
	composer install
	bin/console doctrine:database:create
	bin/console doctrine:schema:update --force
install-test:
	mkdir -p tests/data tests/config/jwt
	php bin/console doctrine:database:create --env=test
	php bin/console doctrine:schema:update --force --env=test
	openssl genrsa -out tests/config/jwt/private.pem -aes256 4096
	openssl rsa -pubout -in tests/config/jwt/private.pem -out tests/config/jwt/public.pem
	make test
test:
	./vendor/bin/behat --format=progress
