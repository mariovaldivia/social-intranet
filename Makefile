#!/bin/bash

UID = $(shell id -u)
DOCKER_BE = application

help: ## Show this help message
	@echo 'usage: make [target]'
	@echo
	@echo 'targets:'
	@egrep '^(.+)\:\ ##\ (.+)' ${MAKEFILE_LIST} | column -t -c 2 -s ':#'

start: ## Start the containers
	docker network create application-network || true
	cp -n docker-compose.yml.dist docker-compose.yml || true
	U_ID=${UID} docker compose up -d

stop: ## Stop the containers
	U_ID=${UID} docker compose stop

restart: ## Restart the containers
	$(MAKE) stop && $(MAKE) start

build: ## Rebuilds all the containers
	docker network create application_network || true
	cp -n docker-compose.yml.dist docker-compose.yml || true
	U_ID=${UID} docker compose build

prepare: ## Runs backend commands
	$(MAKE) composer-install

run: ## starts the Symfony development server
	U_ID=${UID} docker exec -it --user ${UID} ${DOCKER_BE} symfony serve -d --allow-all-ip

logs: ## Show Symfony logs in real time
	U_ID=${UID} docker exec -it --user ${UID} ${DOCKER_BE} symfony server:log

# Backend commands
composer-install: ## Installs composer dependencies
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} composer install --no-interaction

test-db: ## Creates the test database (only needed for db volumes created before docker/mysql/init existed)
	U_ID=${UID} docker compose exec -T db sh /docker-entrypoint-initdb.d/01-test-database.sh

test: ## Migrates the test database and runs PHPUnit
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console --env=test doctrine:migrations:migrate --no-interaction
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/phpunit
# End backend commands

ssh-be: ## bash into the be container
	U_ID=${UID} docker exec -it --user ${UID} ${DOCKER_BE} bash

code-style: ## Runs php-cs to fix code styling following Symfony rules
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php-cs-fixer fix src --rules=@Symfony