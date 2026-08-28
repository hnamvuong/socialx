#!/bin/sh
set -eu

if [ ! -f node_modules/.bin/vite ] || \
   [ ! -f node_modules/.package-lock.json ] || \
   [ package-lock.json -nt node_modules/.package-lock.json ]; then
    npm ci --no-audit --no-fund
fi

exec "$@"
