#!/usr/bin/env bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if [ ! -f "$SCRIPT_FOLDER/../config/secrets/preprod/preprod.decrypt.private.php" ]
then
    echo "You need to create file config/secrets/preprod/preprod.decrypt.private.php from the contents at https://start.1password.com/open/i?a=***REMOVED***&v=***REMOVED***&i=p25pbclejfra6sxl3yrh3dgg24&h=my.1password.com"
    exit 1
fi

if [ "$1" != "--quick" ]
then
  pushd "$SCRIPT_FOLDER"/../
    npm run build
  popd
fi

php bin/console --env=preprod secrets:decrypt-to-local --force

rsync \
  -avc \
  --exclude .DS_Store \
  --exclude .git/ \
  --exclude .idea/ \
  --exclude node_modules/ \
  --exclude var/cache/ \
  --exclude var/log/ \
  --exclude drivers/ \
  --exclude public/generated-content/ \
  --exclude public/phpmyadmin/ \
  --delete \
  "$SCRIPT_FOLDER"/../ \
  www-data@preprod.fyyn.io:/var/www/mercurius-core-business-platform/preprod/

rm -f "$SCRIPT_FOLDER"/../.env.preprod.local

ssh www-data@preprod.fyyn.io -C ' \
cd ~/mercurius-core-business-platform/preprod; \
/usr/bin/env php bin/console --env=preprod cache:clear; \
/usr/bin/env php bin/console --env=preprod doctrine:database:create --if-not-exists --no-interaction; \
/usr/bin/env php bin/console --env=preprod doctrine:migrations:migrate --no-interaction --allow-no-migration --all-or-nothing; \
/usr/bin/env php bin/console --env=preprod messenger:stop-workers --no-interaction;
'
