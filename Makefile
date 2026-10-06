# Vars
stack_name=youl_coin
app_container_id = $(shell docker ps --filter name="$(stack_name)_php" -q)
db_container_id = $(shell docker ps --filter name="$(stack_name)_db" -q)
prod_host=yc.youlz.fr
image_name=barlito/youl-coin-api
worker_image_name=barlito/youl-coin-worker
backup_path=/srv/youl_coin/backups
prod_compose_file=docker-compose-prod.yml
assert_timeout=180
deploy_timeout=300

# Config paths
config_cs_fixer=vendor/barlito/utils/config/.php-cs-fixer.dist.php
config_phpcs=vendor/barlito/utils/config/phpcs.xml.dist
config_phpmd=vendor/barlito/utils/config/phpmd.xml

# Include all make rules from submodule
include make/entrypoint.mk

### Overrides — submodule rules adaptées au projet

# Rolling stack deploy, never `stack rm`: a recreated service has nothing to roll back to
deploy.prod:
	make db.backup
	make docker.deploy.prod
	make deploy.assert_image
	make deploy.assert_image assert_service=message_worker assert_image_name=$(worker_image_name)
	castor barlito:castor:wait-db-container
	make doctrine.migrate
	make smoke.test
	make deploy.prune

# Waits for convergence or rollback; a crash-looping new service never converges, assert_image reports it
docker.deploy.prod:
	docker compose -f $(prod_compose_file) pull
	@timeout $(deploy_timeout) docker stack deploy --detach=false -c $(prod_compose_file) $(stack_name) \
		|| { rc=$$?; [ $$rc -eq 124 ] && echo "⚠ $(stack_name) not converged after $(deploy_timeout)s"; [ $$rc -eq 124 ]; }

# Dump pg_dump dans le volume host $(backup_path) (monté dans le container db).
# Format -F c (custom, compressé). Skippé silencieusement si la DB n'est pas démarrée
# (cas 1er deploy).
db.backup:
	@cid="$$(docker ps --filter name='$(stack_name)_db' -q | head -1)"; \
	if [ -n "$$cid" ]; then \
		ts="$$(date +%Y%m%d-%H%M%S)"; \
		echo "🗄  Backup DB → $(backup_path)/youl_coin-$$ts.dump (container $$cid)"; \
		docker exec -t "$$cid" sh -c "pg_dump -U postgres -F c -d youl_coin -f /backups/youl_coin-$$ts.dump"; \
	else \
		echo "ℹ  DB container introuvable, backup skippé (1er deploy ?)"; \
	fi

# Swarm rollbacks exit 0: fail unless the service really runs the tag, healthy (php-make-rules castor task)
assert_service=php
assert_image_name=$(image_name)
assert_settle=10
deploy.assert_image:
	castor barlito:castor:assert-deployed $(stack_name)_$(assert_service) $(assert_image_name):$(TAG) --timeout=$(assert_timeout) --settle=$(assert_settle)

# Removes the stack's stopped containers (old tasks); prune never touches running ones
deploy.prune:
	docker container prune -f --filter label=com.docker.stack.namespace=$(stack_name)

# Smoke test : curl GET / → fail si non-2xx. Sert de garde-fou post-deploy/update.
smoke.test:
	@echo "🩺 Smoke test https://$(prod_host)/..."
	@curl -fsS -o /dev/null -w "  HTTP %{http_code}\n" https://$(prod_host)/ || (echo "❌ Smoke test KO" && exit 1)
	@echo "✓ Smoke test OK"

quality: check_style
