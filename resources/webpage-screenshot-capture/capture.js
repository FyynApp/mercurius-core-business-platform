const puppeteer = require('puppeteer');

(async () => {

    const targetUrl = process.env.TARGET_URL;
    const screenshotFilePath = process.env.SCREENSHOT_FILE_PATH;

    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    await page.setViewport({width: 1280, height: 1280});
    await page.setUserAgent('Mercurius Webpage Screenshot Capture Agent using Chromium and Puppeteer');

    await page.authenticate({'username':'preprod', 'password': 'Juhnbd73Ztg!hd'});
    await page.goto(targetUrl);
    await page.screenshot({path: screenshotFilePath});

    await browser.close();
})();
