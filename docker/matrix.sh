#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

JOBS=1
ALL=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --all)
      ALL=1
      shift
      ;;
    -j)
      JOBS=$2
      shift 2
      ;;
    *)
      echo "Usage: docker/matrix.sh [--all] [-j N]" >&2
      exit 1
      ;;
  esac
done

PHP_VERSIONS=(7.3 7.4 8.0 8.1 8.2 8.3 8.4 8.5)
LARAVEL_VERSIONS=('^8.0' '^9.0' '^10.0' '^11.0' '^12.0' '^13.0')
DATABASES=(sqlite mysql pgsql)

if [[ "$ALL" -eq 0 ]]; then
  PHP_VERSIONS=(8.3)
  LARAVEL_VERSIONS=('^12.0')
fi

is_excluded() {
  grep -qxF "$1 $2 $3" docker/excludes.txt
}

cells=()

for php in "${PHP_VERSIONS[@]}"; do
  for laravel in "${LARAVEL_VERSIONS[@]}"; do
    for db in "${DATABASES[@]}"; do
      is_excluded "$php" "$laravel" "$db" && continue

      cells+=("$php" "$laravel" "$db")
    done
  done
done

total=$((${#cells[@]} / 3))

if [[ "$total" -eq 0 ]]; then
  echo "No matrix cells to run."
  exit 0
fi

if printf '%s\0' "${cells[@]}" | xargs -0 -n 3 -P "$JOBS" docker/run-cell.sh; then
  echo ""
  echo "Done: $total/$total passed, 0 failed"
  exit 0
fi

echo ""
echo "Done: some of $total cells failed"
exit 1
