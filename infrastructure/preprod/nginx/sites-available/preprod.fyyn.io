server {

  server_name preprod.fyyn.io;

  charset utf-8;

  client_max_body_size 50M;

  access_log /var/log/nginx/preprod.fyyn.io.access.log combined gzip buffer=4k flush=5m;
  error_log /var/log/nginx/preprod.fyyn.io.error.log;

  root /var/www/mercurius-core-business-platform/preprod/public;
  index index.php;

  location /nginx_status {
    stub_status on;
    access_log off;
    allow 127.0.0.1;
    deny all;
  }

  location ~ /\. { deny all; }

  location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_param APP_ENV "preprod";
    fastcgi_hide_header Forwarded;
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
  }

  location / {
    if (-f $request_filename) {
      expires max;
      break;
    }
    rewrite ^(.*) /index.php last;
  }


    listen [::]:443 ssl ipv6only=on; # managed by Certbot
    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/preprod.fyyn.io/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/preprod.fyyn.io/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot

}

server {
    if ($host = preprod.fyyn.io) {
        return 301 https://$host$request_uri;
    } # managed by Certbot



  server_name preprod.fyyn.io;

  listen 80;
  listen [::]:80;
    return 404; # managed by Certbot


}