#!/bin/bash

./vendor/bin/openapi --bootstrap ./development/swagger-constants.php --output ./public/data/swagger.yaml ./development/swagger-v1.php ./app/Http/Controllers/
