stack_name=youl_coin

# Container
app_container_id = $(shell docker ps --filter name="$(stack_name)_nginx" -q)

.PHONY: bash
bash:
	docker exec -it -u root $(app_container_id) bash

.PHONY: deploy
deploy:
	docker stack deploy -c docker-compose.yml $(stack_name)

.PHONY: undeploy
undeploy:
	docker stack rm $(stack_name)

.PHONY: restart-messenger-worker
restart-messenger-worker:
	docker exec -it -u root $(app_container_id) supervisorctl restart messenger-consume:*

.PHONY: fixtures
fixtures:
	docker exec -it $(app_container_id) bin/console hautelook:fixtures:load

.PHONY: cs-fixer
cs-fixer:
	docker exec $(app_container_id) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix -v src
