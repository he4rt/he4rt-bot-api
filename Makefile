#Dockerfile vars
alpver=3.12
kctlver=1.18.0

#vars
IMAGENAME=my_kubectl
REPO=my.registry
IMAGEFULLNAME=${REPO}/${IMAGENAME}:${KUBECTL_VERSION}

.PHONY: help build push all

help:
	    @echo "Makefile arguments:"
	    @echo ""
	    @echo "build - Build Enviroment"

.DEFAULT_GOAL := all

build:
	    @docker-compose up -d --build
	    @docker exec -it discord-bot-api php artisan key:generate
up:
	    @docker-compose up -d
down:
	    @docker-compose down
bash:
	    @docker exec -it discord-bot-api sh
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
