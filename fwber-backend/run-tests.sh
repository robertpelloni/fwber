#!/bin/bash
# Run tests inside the Docker container with the correct environment variables
docker exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: fwber-laravel-prod php artisan test "$@"
