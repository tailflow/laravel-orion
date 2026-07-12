#!/usr/bin/env bash
set -euo pipefail

php=$1
laravel=$2
db=$3
slug="$(printf '%s-%s-%s' "$php" "$laravel" "$db" | tr -d '^' | tr '.' '-')"

export COMPOSE_PROJECT_NAME="orion-${slug}"
export PHP_VERSION=$php

cd "$(dirname "$0")/.."

echo "=== PHP $php | Laravel $laravel | $db ==="

trap 'docker compose down -v --remove-orphans' EXIT

docker compose build test -q

deps=()

if [[ "$db" == sqlite ]]; then
  deps=(--no-deps)
fi

docker compose run --rm ${deps[@]+"${deps[@]}"} \
  -e "LARAVEL_VERSION=${laravel}" \
  -e "DATABASE=${db}" \
  test bash docker/test.sh
