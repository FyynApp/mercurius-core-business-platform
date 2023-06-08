<?php

namespace App\Tests\ApplicationTests\Scenario\BrowserExtension;

use App\Tests\ApplicationTests\Helper\BrowserExtensionHelper;
use App\Tests\ApplicationTests\Helper\RecordingSessionHelper;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Recordings\Infrastructure\SymfonyMessage\GenerateMissingVideoAssetsCommandSymfonyMessage;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;


class RegisteredUserWorkflowTest
    extends WebTestCase
{
    use MailerAssertionsTrait;

    /**
     * @throws Exception
     */
    public function testRecordingWorks(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => RegisteredExtensionOnlyUserFixture::EMAIL]);

        $client->loginUser($user);

        BrowserExtensionHelper::createRecordingSession($client);

        $structuredResponse = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $recordingSessionId = $structuredResponse['settings']['recordingSessionId'];
        $recordingSessionFinishedTargetUrl = $structuredResponse['settings']['recordingSessionFinishedTargetUrl'];
        $postUrl = $structuredResponse['settings']['postUrl'];


        RecordingSessionHelper::uploadChunks(
            $client,
            $postUrl,
            $recordingSessionId
        );

        $structuredResponse = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertSame(
            200,
            $structuredResponse['status']
        );

        $this->assertSame(
            "http://localhost/generated-content/recording-sessions/$recordingSessionId/recording-preview-video-poster.webp",
            $structuredResponse['preview']
        );

        $this->assertStringStartsWith(
            "http://localhost/recordings/recording-sessions/$recordingSessionId/recording-preview-asset-redirect?random=",
            $structuredResponse['previewVideo']
        );


        $client->followRedirects(false);
        $client->request(
            'GET',
            $recordingSessionFinishedTargetUrl
        );


        // Because this is a registered user, we expect async asset generation to have started
        /** @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.async');
        $this->assertCount(1, $transport->getSent());

        /** @var GenerateMissingVideoAssetsCommandSymfonyMessage $message */
        $message = $transport->getSent()[0]->getMessage();

        $this->assertSame(
            GenerateMissingVideoAssetsCommandSymfonyMessage::class,
            $message::class
        );

        $videoId = $message->getVideoId();


        $crawler = $client->followRedirect();

        $this->assertSame(
            'http://localhost/en/my/recordings/videos/',
            $crawler->getUri()
        );

        $this->assertSelectorTextSame(
            'title',
            'Fyyn — Video Library'
        );

        $this->assertSelectorNotExists(
            '[data-test-class="video-presentationpage-template-title"]'
        );


        $this->assertStringContainsString(
            "background-image: url('http://localhost/generated-content/video-assets/$videoId/poster-still.webp');",
            $crawler
                ->filter('[data-test-class="videoManageWidgetPosterStill"]')
                ->first()
                ->attr('style')
        );

        $this->assertStringContainsString(
            "background-image: url('/generated-content/video-assets/$videoId/poster-animated.webp');",
            $crawler
                ->filter('[data-test-class="videoManageWidgetPosterAnimated"]')
                ->first()
                ->attr('style')
        );
    }

    public function testVideoOnlyLandingpage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => RegisteredExtensionOnlyUserFixture::EMAIL]);

        $client->loginUser($user);

        RecordingSessionHelper::makeRecordingSession($client);

        $crawler = $client->request(
            'GET',
            '/en/my/recordings/videos/'
        );

        $landingpageUrl = $crawler
            ->filter('[data-test-class="videoManageWidgetShowLandingpageCta"]')
            ->first()
            ->attr('href');

        $client->request('GET', $landingpageUrl);

        $this->assertSelectorTextContains(
            '[data-test-id="videoShowWithVideoOnlyPresentationpageTemplateOwnerViewNote"]',
            'Your own domain name in the address bar?'
        );

        $client->request('GET', '/');
        $createAccountButton = $crawler->selectButton('Sign out');
        $form = $createAccountButton->form();
        $client->submit($form);

        $client->request('GET', $landingpageUrl);

        $this->assertSelectorNotExists(
            '[data-test-id="videoShowWithVideoOnlyPresentationpageTemplateOwnerViewNote"]'
        );

        $this->assertSelectorTextSame(
            '[data-test-id="videoShowWithVideoOnlyPresentationpageTemplateGetForFreeTopNavCta"]',
            'Get the free browser extension'
        );
    }
}
