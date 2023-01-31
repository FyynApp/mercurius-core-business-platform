#!/usr/bin/env bash

ENV="$1"

mkdir -p /var/tmp/mercurius-core-business-platform/customdomain_setup_tasks
chmod 0777 /var/tmp/mercurius-core-business-platform/customdomain_setup_tasks

while true
do
  for FILENAME in /var/tmp/mercurius-core-business-platform/customdomain_setup_tasks/*.task
  do

    if [ -f "$FILENAME" ]
    then

      echo "Processing file $FILENAME..."
          DOMAIN=$(cat "$FILENAME")

          echo "Setting up domain $DOMAIN..."

          /usr/bin/cat << EOT > /etc/nginx/sites-enabled/customdomain_$DOMAIN
server {
    server_name $DOMAIN;

    listen 80;
    listen [::]:80;

    charset utf-8;

    access_log /var/log/nginx/$DOMAIN.access.log combined gzip buffer=4k flush=5m;
    error_log /var/log/nginx/$DOMAIN.error.log;

    root /dev/null;

    location / {
      proxy_pass https://preprod.fyyn.io;
    }
}
EOT

          /usr/sbin/nginx -t

          if [ "$?" != "0" ]
          then
            echo "nginx config has errors."
            exit 1
          fi

          /usr/sbin/service nginx restart

          /usr/bin/certbot --non-interactive --keep-until-expiring --nginx --domain $DOMAIN

          if [ "$?" != "0" ]
          then
            echo "certbot reported errors."
            exit 2
          fi

          /usr/sbin/service nginx restart

          rm "$FILENAME"
    fi

    sleep 2
  done

  sleep 2
done
