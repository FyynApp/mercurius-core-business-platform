# Mercurius - Core Business Platform

## How to set this project up for local development

### On an Intel-based macOS system

- Install [Homebrew](https://brew.sh/).

- Install [Docker Desktop](https://www.docker.com/products/docker-desktop) (choose " Intel Chip").

- Install NVM (Node Version Manager) by running `brew install nvm`.

- Install all required PHP packages: `brew install php@8.1 composer`.

- Install other required packages: `brew install ffmpeg mysql-client`.

- Install the [Symfony CLI](https://symfony.com/download#step-1-install-symfony-cli) (only follow "Step 1" of the instructions).

Now clone this repository and cd into the folder containing this `README.md` file.

- Start the database Docker container: `docker run --name mcbp-db -p 127.0.0.1:3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mariadb:10.6.7 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci`.

- Install PHP dependencies: `composer install`.

- Set up Node.js: `nvm install && nvm use`.

- Install Node.js dependencies: `npm install --no-save`.

- Start the NPM-based build watcher: `npm run watch`. Keep the terminal window open and running in the background.

- Open a new Terminal window.

- In the new Terminal window, in the project folder, run the test suite: `bash bin/run-tests.sh`.

- If this succeeds, run `~/.symfony5/bin/symfony server:start`. Keep the terminal window open and running in the background.

- Open a new Terminal window.

- In the new Terminal window, in the project folder, run the background jobs process: `php bin/console --no-debug messenger:consume async`. Keep the terminal window open and running in the background.

- Open `http://127.0.0.1:8000/` in your browser. Enjoy!


## Notes on setting up the preprod system

    apt-get install php8.1-cli php8.1-curl php8.1-fpm php8.1-xml php8.1-mbstring php8.1-mysql php8.1-intl php8.1-gd php8.1-opcache php8.1-bcmath php8.1-zip php8.1-dev php8.1-apcu php-pear php-igbinary libzstd-dev mariadb-server mariadb-client nginx apache2-utils net-tools ffmpeg certbot python3-certbot-nginx composer unattended-upgrades

    certbot --nginx -d preprod.fyyn.io


## dev setup

    docker run --name mcbp-db -p 127.0.0.1:3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mariadb:10.6.7 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci

