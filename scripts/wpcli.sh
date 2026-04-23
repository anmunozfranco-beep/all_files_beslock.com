#!/usr/bin/env bash
set -euo pipefail

# Helper: run WP-CLI inside the Docker compose service
if [ "$#" -eq 0 ]; then
  echo "Usage: ./scripts/wpcli.sh <wp-cli-args>"
  echo "Example: ./scripts/wpcli.sh plugin list --path=/var/www/html"
  exit 1
fi

docker compose -f docker-compose.wp.yml run --rm wpcli wp --path=/var/www/html "$@"
