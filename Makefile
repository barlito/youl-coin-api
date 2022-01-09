stack_name=youl_coin

# Container
app_container_id = $(shell docker ps --filter name="$(stack_name)_nginx" -q)

# Include rules to check code style
include make/code_style.mk

# Include utilities rules
include make/utils.mk

.PHONY: bash
bash:
	docker exec -it -u root $(app_container_id) bash

.PHONY: deploy
deploy:
	docker-compose pull
	# Sleep 5 is to wait the container
	docker stack deploy -c docker-compose.yml $(stack_name) && sleep 5
	make composer_install
	make doctrine_migrate
	make doctrine_load_fixtures
	make security_check
#launch messenger consumer

phpunit:
	docker exec -it -u root $(app_container_id) ./vendor/bin/simple-phpunit

.PHONY: undeploy
undeploy:
	docker stack rm $(stack_name)

.PHONY: restart-messenger-worker
restart-messenger-worker:
	docker exec -it -u root $(app_container_id) supervisorctl restart messenger-consume:*

.PHONY: fixtures
fixtures:
	docker exec -it $(app_container_id) bin/console hautelook:fixtures:load
