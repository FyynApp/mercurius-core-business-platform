# Mercurius - Core Business Platform for Fyyn.io
#
## How to set this project up for local development

### On an Intel-based macOS system

#### Setup

- Install [Homebrew](https://brew.sh/).

- Install [Docker Desktop](https://www.docker.com/products/docker-desktop) (choose "Download for Mac - Apple Chip").

- Install NVM (Node Version Manager) by running `brew install nvm`.

- Install all required PHP packages: `brew tap shivammathur/php && brew install php@8.2 composer`.

- Install other required packages: `brew install ffmpeg@4 mysql-client libgd`. Note that you need FFmpeg 4.x - a 5.x version will not work!

- `brew link --force ffmpeg@4`

- `brew link --overwrite ffmpeg@4`

- Install the [Symfony CLI](https://symfony.com/download#step-1-install-symfony-cli) (use the Homebrew approach if possible).

Now clone this repository and cd into the folder containing this `README.md` file.

- Start the database Docker container: `docker run --name mcbp-db -p 127.0.0.1:3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mariadb:10.6.11 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci`.

- Install PHP dependencies: `composer install`.

- Set up the database: `php bin/console doctrine:database:create`.

- Apply all database migrations: `php bin/console doctrine:migrations:migrate`.

- Set up Node.js: `nvm install && nvm use`.

- Install Node.js dependencies: `npm install --no-save`.

- Start the NPM-based build watcher: `npm run watch`. Keep the terminal window open and running in the background.

- Open a new Terminal window.

- In the new Terminal window, in the project folder, run the test suite: `bash bin/run-tests.sh`.

- If this succeeds, run `symfony server:start`. Keep the terminal window open and running in the background.

- Open a new Terminal window.

- In the new Terminal window, in the project folder, run the background jobs process: `php bin/console --no-debug messenger:consume async -vv`. Keep the terminal window open and running in the background.

- Open `http://127.0.0.1:8000/` in your browser. Enjoy!

- In another Terminal window, run `mkdir -p /var/tmp/mercurius-core-business-platform/dev/tus-uploads`


#### Getting back to work

Coming from the setup described above, you should always be able to get back to work after "destructive" events (like reboots) by doing the following (most command require you to `cd` into the project folder containing this `README.md` file):

- Make sure that your Terminal sessions use the right PHP version - `php --version` should show `PHP 8.2.0` or higher. If not, try to fix this by running `brew unlink php && brew link --force php`.

- Make sure that the MariaDB database Docker container is running: `docker start mcbp-db`.

- Applying database migrations is idempotent and never hurts: `php bin/console doctrine:migrations:migrate`.

- In a dedicated Terminal window, start the NPM-based build watcher: `nvm install && npm run watch`. Keep the terminal window open and running in the background.

- In the new Terminal window, run the background jobs process: `php bin/console --no-debug messenger:consume async`. Keep the terminal window open and running in the background.

- In a dedicated Terminal window, start the Symfony web server: `symfony server:start`. Keep the terminal window open and running in the background.

- In another Terminal window, in the root folder of the app, run `php bin/console --no-debug messenger:consume async -vv --limit 1` to run 1 background task

- Open `http://127.0.0.1:8000/` in your browser. Enjoy!


#### Ensuring up-to-date dependencies and DB structure after pulling changes

After pulling changes from the repository, you should always run the following command (this command requires you to `cd` into the project folder containing this `README.md` file):

- `bash bin/update-dev-system.sh`


## Notes on setting up the preprod system

    apt-get update

    apt-get install libzstd-dev mariadb-server mariadb-client nginx apache2-utils net-tools ffmpeg certbot python3-certbot-nginx composer unattended-upgrades software-properties-common

    LC_ALL=C.UTF-8 sudo add-apt-repository ppa:ondrej/php

    apt-get update

    apt-get install php8.2-cli php8.2-curl php8.2-fpm php8.2-xml php8.2-mbstring php8.2-mysql php8.2-intl php8.2-gd php8.2-opcache php8.2-bcmath php8.2-zip php8.2-dev php8.2-apcu php-pear php-igbinary composer

    certbot --nginx -d preprod.fyyn.io


## dev setup

    docker run --name mcbp-db -p 127.0.0.1:3306:3306 -e MYSQL_ROOT_PASSWORD=secret -d mariadb:10.6.11 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
