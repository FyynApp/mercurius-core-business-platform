# prod setup notes

    apt-get install libzstd-dev mariadb-server mariadb-client nginx apache2-utils net-tools ffmpeg certbot python3-certbot-nginx composer unattended-upgrades software-properties-common munin supervisor awscli

    mysql
    > GRANT ALL PRIVILEGES ON mercurius_core_business_platform_prod.* TO 'mercurius_prod'@localhost IDENTIFIED BY '<redacted>';
    # See https://start.1password.com/open/i?a=***REMOVED***&v=***REMOVED***&i=pbsc52v6f5nfhmivmbepyavbsa&h=my.1password.com for password

    LC_ALL=C.UTF-8 sudo add-apt-repository ppa:ondrej/php

    apt-get install php8.3-cli php8.3-curl php8.3-fpm php8.3-xml php8.3-mbstring php8.3-mysql php8.3-intl php8.3-gd php8.3-opcache php8.3-bcmath php8.3-zip php8.3-dev php8.3-apcu php-pear php-igbinary

    # Copy infrastructure/hosting_contexts/prod/etc/php/8.3/fpm/php.ini
    # Copy infrastructure/hosting_contexts/prod/etc/php/8.3/fpm/pool.d/www.conf

    service php8.3-fpm restart

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

    exit

    # Deploy prod

    # Copy infrastructure/hosting_contexts/prod/etc/supervisor/conf.d/mercurius-core-business-platform-symfony-messages-consumer.conf
    # Copy infrastructure/hosting_contexts/prod/etc/supervisor/conf.d/mercurius-core-business-platform-setup-custom-domain.conf

    service supervisor stop
    service supervisor start

    # as user www-data, configure the aws cli for access to Contabo S3 using the credentials in https://start.1password.com/open/i?a=***REMOVED***&v=***REMOVED***&i=swv56noijpqo35w546xxbpf2xq&h=my.1password.com

    aws configure

    # as user root, put file infrastructure/hosting_contexts/prod/opt/backup.sh to /opt/backup.sh
    
    # as user root, put file infrastructure/hosting_contexts/prod/etc/cron.d/backup to /etc/cron.d/backup
