CSFIXER_OPT ?=
RECTOR_OPT ?=

check_style:
	make app.phpcs
	make app.phpmd
	make app.cs_fixer.dry_run

phpcs:
	docker exec -t $(app_container_id) vendor/bin/phpcs --standard=make/phpcs.xml.dist src/ tests/

phpmd:
	docker exec -t $(app_container_id) vendor/bin/phpmd src/ ansi make/phpmd.xml --exclude src/Migrations/,tests/

cs_fixer:
	docker exec -t $(app_container_id) php -d "memory_limit=-1" vendor/bin/php-cs-fixer fix --diff --config=make/.php-cs-fixer.dist.php $(CSFIXER_OPT)

cs_fixer.dry_run:
	docker exec -t $(app_container_id) php -d "memory_limit=-1" vendor/bin/php-cs-fixer fix --dry-run --diff --config=make/.php-cs-fixer.dist.php $(CSFIXER_OPT)
