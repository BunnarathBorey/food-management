
build:
	# This command must be indented with a tab, not spaces
	docker-compose build

up:
	# Also, ensure this command is tab-indented
	docker-compose up -d

down:
	docker-compose down

sh:
	# Tab indentation for this command
	docker-compose exec -it app sh

bash:

	docker exec -u root -it app bash

composer-install:
	# Tab indentation for this command
	docker exec app composer install

composer-update:
	# Tab indentation for this command
	docker exec app composer update
