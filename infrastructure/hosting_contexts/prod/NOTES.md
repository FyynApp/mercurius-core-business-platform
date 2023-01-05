# prod setup notes

    apt-get install libzstd-dev mariadb-server mariadb-client nginx apache2-utils net-tools ffmpeg certbot python3-certbot-nginx composer unattended-upgrades software-properties-common munin

    mysql
    > GRANT ALL PRIVILEGES ON mercurius_core_business_platform_prod.* TO 'mercurius_prod'@localhost IDENTIFIED BY '<redacted>';
    # See https://start.1password.com/open/i?a=***REMOVED***&v=***REMOVED***&i=pbsc52v6f5nfhmivmbepyavbsa&h=my.1password.com for password

    LC_ALL=C.UTF-8 sudo add-apt-repository ppa:ondrej/php

    apt-get install php8.2-cli php8.2-curl php8.2-fpm php8.2-xml php8.2-mbstring php8.2-mysql php8.2-intl php8.2-gd php8.2-opcache php8.2-bcmath php8.2-zip php8.2-dev php8.2-apcu php-pear php-igbinary composer

    # Copy infrastructure/hosting_contexts/prod/etc/php/8.2/fpm/php.ini
    # Copy infrastructure/hosting_contexts/prod/etc/php/8.2/fpm/pool.d/www.conf

    service php8.2-fpm restart

    # Copy infrastructure/hosting_contexts/prod/etc/nginx/sites-available/app.fyyn.io_before_certbot
    #   to /etc/nginx/sites-available/app.fyyn.io
    # Copy infrastructure/hosting_contexts/prod/etc/nginx/htpasswd

    ln -s /etc/nginx/sites-available/app.fyyn.io /etc/nginx/sites-enabled/
    rm /etc/nginx/sites-available/default
    rm /etc/nginx/sites-enabled/default
    service nginx stop 
    service nginx start 

    certbot --nginx -d app.fyyn.io

    # /etc/passwd -> replace `www-data:x:33:33:www-data:/var/www:/usr/sbin/nologin`
    #                   with `www-data:x:33:33:www-data:/var/www:/bin/bash`

    chown www-data:www-data /var/www    
    su - www-data
    mkdir .ssh

    echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDH+W2bMGoJpbyz02ZJUE4RqZtzUx60I4D/Nc8KvIc+/v9pHz48gL/g6o4f1G+eREHIM+hLpL4HPTq/mY/Lg0atQTIBaUpHMLRgvy6tkPRH3yBDepgLeri2zFgz6VjKVEGrVlnd460mLInQcc80SliW4BK+5ftYTgiR/cEVljLWOCsY05VuaJs8tlGOfqSe4bAbMcWrceDwWWSGuizF+ATXu5XT+Akk1TEb8igCWjC8zZMLM/ZtwYAxG4Pq/OILj2Vw68O7fUJEj+BnIQpSfS8+HjfQ9V5VsZEM1/LdcW/z3sVE1D6QwhSJzkLwFy5b04Mkd6bS4wuYP5fnbpVL6r49qQl3rRMZsO/+umfLHv0v5JkCHahxf0wx3UGhzH3/VHLbpHa3dnL2M7oHl1+xWOnbhjLqDE3Yx66Ld71rmLBiA7CE9xM6LWXvMQ2sTgdeH6ju0UBVcLZY/iqUjbwo7SYM8+Aeyh4gv9FtyT7ym7QZTJnzdTBRMU4BRaHZRo6K8vOnLBN3DcU+jhBb1LDJe04YdybD2OIMzliTJQseva9vSMoM3dryWMS3fcag24OOsT+GWwY7IO6KaGQfalyoeBBKFlVSHE46TzNJTrriNa6zn37pcjBvGlF37nxuOFMqWvEteywAw2SYBOoITPpHbaYv3mmZ9DK741COz89nJxluSw== manuel@kiessling.net" > .ssh/authorized_keys

    chmod 0700 .ssh
    chmod 0600 .ssh/authorized_keys

    mkdir -p mercurius-core-business-platform/prod
