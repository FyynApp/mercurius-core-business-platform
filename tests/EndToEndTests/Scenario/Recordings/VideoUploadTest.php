<?php

namespace App\Tests\EndToEndTests\Scenario\Recordings;

use App\Tests\EndToEndTests\Helper\AccountHelper;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\PantherTestCase;

class VideoUploadTest extends PantherTestCase
{
    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    public function test(): void
    {
        $client = static::createPantherClient();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);

        /** @var null|User $user */
        $user = $userRepository->findOneBy(['email' => RegisteredExtensionOnlyUserFixture::EMAIL]);

        AccountHelper::signIn($client, $user);

        $crawler = $client->refreshCrawler();
        $crawler->filter('[data-test-id="uppyVideoUploadDashboardOpenCta"]')->click();

        $client->waitFor('.uppy-Dashboard-input');

        /** @var RemoteWebElement $element */
        $element = $crawler->findElement(
            WebDriverBy::cssSelector('.uppy-Dashboard-input')
        );

        $element->setFileDetector(
            new LocalFileDetector()
        )->sendKeys(__DIR__ . '/../../../Resources/fixtures/videos/upload-video.mov');

        $client->waitFor(
            '[data-test-class="videoUploadProcessingWidget"]'
        );

        $this->assertSelectorExists('[data-test-class="videoUploadProcessingWidget"]');
    }
}
