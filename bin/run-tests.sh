#!/usr/bin/env bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

set -e

pushd "$SCRIPT_FOLDER/.."
  rm -rf var/cache/test
  /usr/bin/env php bin/console --env=test doctrine:database:create --if-not-exists --no-interaction
  /usr/bin/env php bin/console --env=test doctrine:migrations:migrate --no-interaction
  /usr/bin/env php bin/console --env=test doctrine:fixtures:load --no-interaction
  /usr/bin/env php bin/phpunit
popd
