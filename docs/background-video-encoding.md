as user www-data:

    cd mercurius-core-business-platform/preprod/
    screen
    while true; do php bin/console --env=preprod messenger:consume async -vv --limit 1; done
