# Setup

    apt install docker.io

Last line in /etc/group: docker:x:119:www-data

    mkdir puppeteer
    cd puppeteer
    chmod 0777
 
    docker run \
        -i \
        --init \
        --cap-add=SYS_ADMIN \
        --rm \
        --env TARGET_URL="https://preprod.fyyn.io/de/my/presentationpages/1ed3037c-1fc5-6dce-8388-8509d02ed3e8/preview" \
        --env SCREENSHOT_FILE_PATH="/host/1ed3037c-1fc5-6dce-8388-8509d02ed3e8.png" \
        -v $(pwd):/host ghcr.io/puppeteer/puppeteer:latest \
        node -e "`cat capture.js`"
