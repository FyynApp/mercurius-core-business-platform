#!/usr/bin/env bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

rsync \
  -avc \
  --exclude .git/ \
  --exclude .idea/ \
  --exclude node_modules/ \
  --exclude var/cache/ \
  --exclude var/log/ \
  --exclude public/generated-content/ \
  --delete \
  "$SCRIPT_FOLDER"/../ \
  www-data@89.58.33.15:/var/www/mercurius-core-business-platform/preprod/
