.DEFAULT_GOAL := help

.PHONY: help
help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: route-list
route-list: ## List all registered routes
	@php artisan route:list --except-vendor --ansi

.PHONY: pint
pint: ## Run Pint code style fixer
	@XDEBUG_MODE=off $(CURDIR)/vendor/bin/pint --parallel

.PHONY: test-pint
test-pint: ## Run Pint code style fixer in test mode
	@XDEBUG_MODE=off $(CURDIR)/vendor/bin/pint --test --parallel

.PHONY: rector
rector: ## Run Rector
	@$(CURDIR)/vendor/bin/rector process

.PHONY: test-rector
test-rector: ## Run Rector in test mode
	@$(CURDIR)/vendor/bin/rector process --dry-run

.PHONY: phpstan
phpstan: ## Run PHPStan
	@$(CURDIR)/vendor/bin/phpstan analyse --ansi --memory-limit=2G

.PHONY: p
p: phpstan ## Alias for phpstan

.PHONY: test-phpstan
test-phpstan: ## Run PHPStan in test mode
	@$(CURDIR)/vendor/bin/phpstan analyse --ansi --memory-limit=2G

.PHONY: format
format: rector pint ## Run Pint and Rector and try to fixes the source code

.PHONY: f
f: format ## Alias for format

.PHONY: check
check: test-rector test-pint test-phpstan ## Run Pint, PHPStan with Rector in dry-run mode

.PHONY: test
test: ## Run all tests
	@$(CURDIR)/vendor/bin/pest --compact

.PHONY: t
t: test ## Alias for test

.PHONY: test-shards
test-shards: ## Regenerate tests/.pest/shards.json timings (sequential)
	@php artisan test --compact --update-shards

.PHONY: test-unit
test-unit: ## Run unit tests
	@$(CURDIR)/vendor/bin/pest --compact --group=unit

.PHONY: test-feature
test-feature: ## Run feature tests
	@$(CURDIR)/vendor/bin/pest --compact --group=feature

.PHONY: setup-test-db
setup-test-db: ## Create the testing database for running tests
	@php artisan migrate --env=testing --no-interaction --force

.PHONY: migrate-fresh
migrate-fresh: ## Run migrations and seed the database
	@php artisan migrate:fresh --seed

.PHONY: env-up
env-up: ## Start the development environment
	@docker compose --file docker-compose.yml up --detach

.PHONY: env-down
env-down: ## Stop the development environment (removes images and volumes)
	@docker compose --file docker-compose.yml down --rmi all --volumes

.PHONY: dev
dev: ## Start the server
	@composer run-script dev

.PHONY: setup
setup: ## Setup the project
	@composer run-script setup

.PHONY: import-db
import-db: ## Import a PostgreSQL dump file (usage: make import-db file=path/to/dump)
	@PGHOST=localhost PGUSER=postgres PGPASSWORD=postgres pg_restore -x -O -cC -j 8 -d postgres $(file)

.PHONY: bot
bot: ## Run the Discord bot
	@php artisan bot:boot

# Fixes the boot error "failed to initialize voice class /
# LibDaveNotFoundException: libdave is required but could not be loaded" — since
# 2026-03-01 Discord mandates the DAVE E2EE protocol for voice.
.PHONY: libdave
libdave: ## Link libdave (DAVE E2EE) into the Discord bot voice probe path
	@mkdir -p "$(CURDIR)/.cache" && ln -sfn "$${LIBDAVE_HOME:-$$HOME/.local/lib/libdave}" "$(CURDIR)/.cache/libdave"

.PHONY: truncate
truncate: ## Truncate laravel.log file
	@truncate -s 0 storage/logs/laravel.log

-include Makefile.local
