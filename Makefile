.PHONY: help build install update test test-coverage test-filter format analyse shell clean

GREEN := \033[0;32m
RESET := \033[0m

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-20s$(RESET) %s\n", $$1, $$2}'

build: ## Build Docker image
	docker compose build

install: ## Install Composer dependencies
	docker compose run --rm app composer install

update: ## Update Composer dependencies
	docker compose run --rm app composer update

test: ## Run all tests
	docker compose run --rm app composer test

test-coverage: ## Run tests with coverage report
	docker compose run --rm app composer test-coverage

test-filter: ## Run filtered tests (ex: make test-filter FILTER=VolpaMail)
	docker compose run --rm app vendor/bin/pest --filter=$(FILTER)

format: ## Format code with Pint
	docker compose run --rm app composer format

analyse: ## Run static analysis with PHPStan
	docker compose run --rm app composer analyse

shell: ## Open shell in container
	docker compose run --rm app sh

clean: ## Remove containers and volumes
	docker compose down -v --remove-orphans
