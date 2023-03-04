<?php

namespace App\Tests\EndToEndTests\Scenario\Recordings;

use App\Tests\EndToEndTests\Helper\AccountHelper;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\PantherTestCase;

class VideoFoldersTest extends PantherTestCase
{
    public function test(): void
    {
        $this->markTestSkipped('Not yet working, see https://bugs.chromium.org/p/chromedriver/issues/detail?id=841');

        $client = static::createPantherClient(['browser' => self::FIREFOX]);
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);

        /** @var null|User $user */
        $user = $userRepository->findOneBy(['email' => RegisteredExtensionOnlyUserFixture::EMAIL]);

        AccountHelper::signIn($client, $user);

        $crawler = $client->request(
            'GET',
            '/en/my/recordings/videos/'
        );

        $createFolderButton = $crawler->selectButton('Create folder');
        $form = $createFolderButton->form();
        $form['name'] = 'Testfolder';
        $client->submit($form);

        $crawler = $client->refreshCrawler();
        $crawler->filter('[data-test-id="uppyVideoUploadDashboardOpenCta"]')->click();

        $client->waitFor('.uppy-Dashboard-input');

        $crawler->findElement(
            WebDriverBy::cssSelector('.uppy-Dashboard-input')
        )->setFileDetector(
            new LocalFileDetector()
        )->sendKeys(__DIR__ . '/../../../Resources/fixtures/videos/upload-video.mov');

        $crawler = $client->waitFor(
            '[data-test-class="videoUploadProcessingWidget"]'
        );

        $this->assertSelectorExists('[data-test-class="videoUploadProcessingWidget"]');

        $actions = new WebDriverActions($client->getWebDriver());

        $actions->dragAndDrop(
            $crawler->findElement(WebDriverBy::cssSelector('[data-test-class="videoUploadProcessingWidget"]')),
            $crawler->findElement(WebDriverBy::cssSelector('[data-test-id="video-folder-0"]'))
        );
    }
}
