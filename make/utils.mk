

composer_install:
	docker exec -t $(app_container_id) composer install --optimize-autoloader --no-interaction

doctrine_migrate:
	docker exec -t $(app_container_id) bin/console doctrine:database:create --if-not-exists
	docker exec -t $(app_container_id) bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

doctrine_migrate_ci:
	docker exec -t $(app_container_id) bin/console doctrine:database:create --if-not-exists --env=test
	docker exec -t $(app_container_id) bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test

doctrine_reset_db:
	docker exec -t $(app_container_id) bin/console doctrine:database:drop --force --if-exists
	docker exec -t $(app_container_id) bin/console doctrine:database:create --if-not-exists

doctrine_load_fixtures:
	docker exec -t $(app_container_id) bin/console hautelook:fixtures:load -n

doctrine_load_fixtures_ci:
	docker exec -t $(app_container_id) bin/console hautelook:fixtures:load -n --env=test

security_check:
	docker run --rm -v $(shell pwd):$(shell pwd) -w $(shell pwd) symfonycorp/cli security:check
