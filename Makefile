# Vars
stack_name=youl_coin
app_container_id = $(shell docker ps --filter name="$(stack_name)_php" -q)
prod_host=yc.youlz.fr
image_name=barlito/youl-coin-api
worker_image_name=barlito/youl-coin-worker
worker_services=message_worker:2
db_user=postgres
db_name=youl_coin

# Config paths
config_cs_fixer=vendor/barlito/utils/config/.php-cs-fixer.dist.php
config_phpcs=vendor/barlito/utils/config/phpcs.xml.dist
config_phpmd=vendor/barlito/utils/config/phpmd.xml

# Include all make rules from submodule
include make/entrypoint.mk

### Project rules

# The test suites need the JWT key pair inside the CI stack
jwt.generate:
	castor generate-jwt-key-pair

quality: check_style
