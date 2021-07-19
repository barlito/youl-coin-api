stack_name= youl_coin

# Container
app_container_id = $(shell docker ps --filter name="$(stack_name)_nginx" -q)

.PHONY: bash
bash:
	docker exec -it $(app_container_id) bash

.PHONY: deploy
deploy: docker-compose.yml
	docker stack deploy -c docker-compose.yml $(stack_name)

.PHONY: undeploy
undeploy:
	docker stack rm $(stack_name)
