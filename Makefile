#Dockerfile vars
alpver=3.12
kctlver=1.18.0

#vars
IMAGENAME=discord-bot-api
REPO=he4rt
IMAGEFULLNAME=${REPO}/${IMAGENAME}:${KUBECTL_VERSION}

help:
	    @echo "Makefile Commands:"
	    @echo "---"
	    @echo "build - Build Enviroment"
	    @echo "---"
	    @echo "up - Up enviroment"
	    @echo "---"
	    @echo "test - Run tests (--filter=ClassName)"
	    @echo "---"
	    @echo "migrate - Run Migrations"

.DEFAULT_GOAL := all

build:
	    @docker-compose up -d --build
	    @docker exec -it discord-bot-api php artisan key:generate
up:
	    @docker-compose up -d
down:
	    @docker-compose down
test:
	    @$(eval testsuite ?= 'all')
	    @$(eval filter ?= '.')
	    @docker exec -it discord-bot-api vendor/bin/phpunit --filter=$(filter) --stop-on-failure
migrate:
	    @docker exec -it discord-bot-api php artisan migrate --database=testing --seed
	    @docker exec -it discord-bot-api php artisan migrate --seed
push:
	    @docker push ${IMAGEFULLNAME}

all: build push
