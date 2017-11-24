api
===

```bash
mkdir var/jwt var/data
openssl genrsa -out var/jwt/private.pem -aes256 4096
openssl rsa -pubout -in var/jwt/private.pem -out var/jwt/public.pem
composer install
bin/console doctrine:database:create
bin/console doctrine:schema:update --force
```
