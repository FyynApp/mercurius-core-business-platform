<?php

namespace App\Tests\ApplicationTests\Scenario\BrowserExtension;

use App\Tests\ApplicationTests\Helper\BrowserExtensionHelper;
use App\Tests\ApplicationTests\Helper\RecordingSessionHelper;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Message\SyncUserToActiveCampaignCommandMessage;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Recordings\Infrastructure\Message\GenerateMissingVideoAssetsCommandMessage;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Mime\Email;


class RegisteredUserWorkflowTest
    extends WebTestCase
{
    use MailerAssertionsTrait;

    public function test(): void
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
        $crawler = $client->request(
            'GET',
            $recordingSessionFinishedTargetUrl
        );


        // Because this is a registered user, we expect async asset generation to have started
        /* @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.async');
        $this->assertCount(1, $transport->getSent());

        /** @var GenerateMissingVideoAssetsCommandMessage $message */
        $message = $transport->getSent()[0]->getMessage();

        $this->assertSame(
            GenerateMissingVideoAssetsCommandMessage::class,
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
            'Fyyn — Recordings'
        );

        $this->assertSelectorTextSame(
            '[data-test-class="video-title"]',
            'Recording 1'
        );

        $this->assertSelectorNotExists(
            '[data-test-class="video-presentationpage-template-title"]'
        );


        $this->assertStringContainsString(
            "background-image: url('/generated-content/video-assets/$videoId/poster-still.webp');",
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
}
