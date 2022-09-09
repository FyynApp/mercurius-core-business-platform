#!/usr/bin/env bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

pushd "$SCRIPT_FOLDER"/../
  npm run build
popd

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
  www-data@preprod.fyyn.io:/var/www/mercurius-core-business-platform/preprod/

ssh www-data@preprod.fyyn.io -C ' \
cd ~/mercurius-core-business-platform/preprod; \
/usr/bin/env php bin/console --env=preprod cache:clear \
'
