#!/usr/bin/env bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

pushd "$SCRIPT_FOLDER/.."
  composer install
  npm install --no-save
  php bin/console --no-debug doctrine:migrations:migrate --no-interaction
popd
