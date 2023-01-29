#!/usr/bin/env bash

NOW=$(date +%Y-%m-%d)

WORKDIR="$(mktemp -d)"

pushd "$WORKDIR"

  source /var/www/mercurius-core-business-platform/prod/.env.prod
  source /var/www/mercurius-core-business-platform/prod/.env.prod.local

  mysqldump \
      -h"$DATABASE_HOST" \
      -u"$DATABASE_USER" \
      -p"$DATABASE_PASSWORD" \
      --skip-lock-tables \
      --max-allowed-packet=268435456 \
      --force \
      --hex-blob \
      "$DATABASE_DB" \
      | gzip -9 -c > "./mercurius-core-business-platform_prod_${NOW}.sql.gz"

  aws \
    --region default \
    --endpoint-url https://eu2.contabostorage.com \
    s3 \
    cp \
    "$WORKDIR/mercurius-core-business-platform_prod_${NOW}.sql.gz" \
    "s3://202206/mercurius/backups/hetzner-server-1943978/sql-dumps/"

  aws \
    --region default \
    --endpoint-url https://eu2.contabostorage.com \
    s3 \
    sync \
    /var/www/mercurius-core-business-platform/prod/public/generated-content/ \
    "s3://202206/mercurius/backups/hetzner-server-1943978/files/var/www/mercurius-core-business-platform/prod/public/generated-content/"

popd

rm -rf "$WORKDIR"
