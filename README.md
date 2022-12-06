# Mercurius - Core Business Platform

## Setting up the preprod system

    apt-get install php8.1-cli php8.1-curl php8.1-fpm php8.1-xml php8.1-mbstring php8.1-mysql php8.1-intl php8.1-gd php8.1-opcache php8.1-bcmath php8.1-zip php8.1-dev php8.1-apcu php-pear php-igbinary libzstd-dev mariadb-server mariadb-client nginx apache2-utils net-tools ffmpeg certbot python3-certbot-nginx composer unattended-upgrades

    certbot --nginx -d preprod.fyyn.io


## dev setup

    docker run --name mcbp-db -p 127.0.0.1:3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mariadb:10.6.7 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci

