#!/usr/bin/env bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if [ ! -f "$SCRIPT_FOLDER/../config/secrets/prod/prod.decrypt.private.php" ]
then
    echo "You need to create file config/secrets/prod/prod.decrypt.private.php from the contents at https://start.1password.com/open/i?a=***REMOVED***&v=***REMOVED***&i=ta7htureskqefhqz7x7h4a7ydq&h=my.1password.com"
    exit 1
fi

if [ "$1" != "--quick" ]
then
  pushd "$SCRIPT_FOLDER"/../
    npm run build
  popd
fi

php bin/console --env=prod secrets:decrypt-to-local --force

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
  --exclude public/public-assets.fyyn.io/ \
  --delete \
  "$SCRIPT_FOLDER"/../ \
  www-data@89.58.33.15:/var/www/mercurius-core-business-platform/prod/

rm -f "$SCRIPT_FOLDER"/../.env.prod.local

ssh www-data@89.58.33.15 -C ' \
cd ~/mercurius-core-business-platform/prod; \
/usr/bin/env php bin/console --env=prod cache:clear; \
/usr/bin/env php bin/console --env=prod doctrine:database:create --if-not-exists --no-interaction; \
/usr/bin/env php bin/console --env=prod doctrine:migrations:migrate --no-interaction --allow-no-migration --all-or-nothing; \
/usr/bin/env php bin/console --env=prod messenger:stop-workers --no-interaction;
'
