<?php

namespace App\Tests\EndToEndTests\Scenario\Homepage;

use App\Tests\EndToEndTests\Helper\AccountHelper;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\PantherTestCase;

class NativeRecorderTest extends PantherTestCase
{
    public function testMobileCreateVideoCta(): void
    {
        $client = self::createPantherClient(
            [],
            [],
            [
                'capabilities' => [
                    'goog:loggingPrefs' => ['browser' => 'ALL'],
                ]
            ]
        );

        $crawler = $client->request(
            'GET',
            '/en/welcome?forceMobileView=true'
        );
        $crawler->filter('[data-test-id="mobileCreateVideoCta"]')->click();

        $client->waitFor('.uppy-Dashboard-input');

        $crawler->findElement(
            WebDriverBy::cssSelector('.uppy-Dashboard-input')
        )->setFileDetector(
            new LocalFileDetector()
        )->sendKeys(__DIR__ . '/../../../Resources/fixtures/videos/upload-video.mov');

        $client->waitFor(
            '[data-test-id="greeting"]'
        );

        $this->assertSelectorTextSame(
            '[data-test-id="greeting"]',
            'Hey there,'
        );
    }
}
