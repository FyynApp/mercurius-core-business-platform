server {

  server_name preprod.fyyn.io;

  resolver 8.8.8.8 valid=30s;

  charset utf-8;

  client_max_body_size 50M;

  access_log /var/log/nginx/preprod.fyyn.io.access.log combined gzip buffer=4k flush=5m;
  error_log /var/log/nginx/preprod.fyyn.io.error.log;

  root /var/www/mercurius-core-business-platform/preprod/public;
  index index.php;

  location /nginx_status {
    auth_basic "Login required";
    auth_basic_user_file /etc/nginx/htpasswd;

    stub_status on;
    access_log off;
    allow all;
    deny all;
  }

  location ~ /\. { deny all; }

  location ~/helpdesk(.*)$ {
    proxy_set_header X-Forwarded-For $remote_addr;
    #proxy_set_header Host $host;
    proxy_pass http://fyynhelpdesk.kinsta.cloud$1;
  }

  set $auth_basic "Login required";

  if ($uri ~ "^/de/presentationpages/") {
    set $auth_basic off;
  }

  if ($uri ~ "^/en/presentationpages/") {
    set $auth_basic off;
  }


  location ~ \.php$ {

    if ($request_method = 'OPTIONS') {
      add_header 'Access-Control-Allow-Origin' '*';
      #
      # Om nom nom cookies
      #
      add_header 'Access-Control-Allow-Credentials' 'true';
      add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
      #
      # Custom headers and headers various browsers *should* be OK with but aren't
      #
      add_header 'Access-Control-Allow-Headers' 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type';
      #
      # Tell client that this pre-flight info is valid for 20 days
      #
      add_header 'Access-Control-Max-Age' 1728000;
      add_header 'Content-Type' 'text/plain charset=UTF-8';
      add_header 'Content-Length' 0;
      return 204;
    }
    if ($request_method = 'POST') {
     add_header 'Access-Control-Allow-Origin' '*';
     add_header 'Access-Control-Allow-Credentials' 'true';
     add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
     add_header 'Access-Control-Allow-Headers' 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type';
    }
    if ($request_method = 'GET') {
     add_header 'Access-Control-Allow-Origin' '*';
     add_header 'Access-Control-Allow-Credentials' 'true';
     add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
     add_header 'Access-Control-Allow-Headers' 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type';
    }

    auth_basic $auth_basic;
    auth_basic_user_file /etc/nginx/htpasswd;

    include snippets/fastcgi-php.conf;
    fastcgi_param APP_ENV "preprod";
    fastcgi_hide_header Forwarded;
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
  }

  location / {

    if (-f $request_filename) {
      expires max;

      add_header 'Access-Control-Allow-Origin' '*';
      add_header 'Access-Control-Allow-Credentials' 'true';
      add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
      add_header 'Access-Control-Allow-Headers' 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type';

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