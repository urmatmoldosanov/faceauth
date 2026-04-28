.PHONY: help lint hash smoke env-check setup deploy-check cleanup

help:
	@echo "Targets:"
	@echo "  make lint               - PHP syntax checks for backend files"
	@echo "  make hash PASS=...      - Generate bcrypt hash for admin/viewer password"
	@echo "  make smoke URL=...      - Run smoke test against backend URL"
	@echo "  make env-check          - Validate required env vars in current shell"
	@echo "  make setup              - Prepare storage folders and permissions"
	@echo "  make deploy-check URL=  - env-check + smoke"
	@echo "  make cleanup DAYS=30    - Cleanup old snapshots/violations"

lint:
	php -l public/api.php
	php -l public/admin/index.php
	php -l public/admin/photo.php
	php -l src/Config.php
	php -l src/Storage.php
	php -l src/Database.php
	php -l src/LicenseCache.php
	php -l src/FaceAuthClient.php
	php -l scripts/generate_password_hash.php
	php -l scripts/cleanup_retention.php

hash:
	@test -n "$(PASS)" || (echo "PASS is required, example: make hash PASS='StrongPassword'" && exit 1)
	php scripts/generate_password_hash.php "$(PASS)"

smoke:
	./scripts/smoke_test.sh "$(or $(URL),http://faceauth-backend.local)"

env-check:
	./scripts/check_env.sh

setup:
	./scripts/setup_storage.sh

deploy-check:
	./scripts/check_env.sh
	./scripts/smoke_test.sh "$(or $(URL),http://faceauth-backend.local)"

cleanup:
	php scripts/cleanup_retention.php "$(or $(DAYS),30)"
