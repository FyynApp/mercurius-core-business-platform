<?php

namespace App\Tests\ApplicationTests\Scenario\BrowserExtension;

use App\Tests\ApplicationTests\Helper\BrowserExtensionHelper;
use App\Tests\ApplicationTests\Helper\RecordingSessionHelper;
use App\VideoBasedMarketing\Account\Infrastructure\Message\SyncUserToActiveCampaignCommandMessage;
use App\VideoBasedMarketing\Recordings\Infrastructure\Message\GenerateMissingVideoAssetsCommandMessage;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Mime\Email;


class UnregisteredUserWorkflowTest
    extends WebTestCase
{
    use MailerAssertionsTrait;

    public function testClaimViaEmail(): void
    {
        $client = static::createClient();

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

        // Verifies that at this point, no asset generation async message has been dispatched
        /* @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.async');
        $this->assertCount(0, $transport->getSent());

        $crawler = $client->followRedirect();

        $this->assertSame(
            'http://localhost/en/account/claim',
            $crawler->getUri()
        );

        $this->assertSame(
            'Hey there,',
            $crawler->filter('h1')->first()->text()
        );


        $buttonCrawlerNode = $crawler->selectButton('Create account');
        $form = $buttonCrawlerNode->form();

        $form['claim_unregistered_user[email]'] = 'foo@bar.de';

        $client->followRedirects(false);
        $client->submit($form);


        /* @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.async');
        $this->assertCount(1, $transport->getSent());

        /** @var SyncUserToActiveCampaignCommandMessage $message */
        $message = $transport->getSent()[0]->getMessage();

        $this->assertSame(
            SyncUserToActiveCampaignCommandMessage::class,
            $message::class
        );

        $this->assertCount(1, $message->getContactTags());

        $this->assertSame(
            9,
            $message->getContactTags()[0]->value
        );


        $this->assertEmailCount(1);

        /** @var Email $email */
        $email = $this->getMailerMessage();

        $crawler->clear();
        $crawler->addHtmlContent($email->getHtmlBody());

        $this->assertSame(
            "It's nearly done!",
            $crawler->filter('h2')->first()->text()
        );


        $client->followRedirects(false);
        $client->request(
            'GET',
            $crawler->filter('a')->first()->attr('href')
        );

        // Verifies that at this point, the asset generation async message has been dispatched
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


        $client->followRedirect();
        $client->followRedirect();
        $crawler = $client->followRedirect();

        $this->assertSelectorTextSame(
            'title',
            'Fyyn — Videos'
        );

        $this->assertSelectorTextContains(
            'body',
            'Your email address has been verified.'
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

    public function testClaimViaThirdPartyLinkedInAuth(): void
    {
        $client = static::createClient();

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

        $client->followRedirects();

        $client->request(
            'GET',
            $recordingSessionFinishedTargetUrl
        );

        $this->assertSelectorTextSame(
            '[data-test-id="claim-by-thirdpartyauth-linkedin-text"]',
            'Sign up with LinkedIn.'
        );

        $client->followRedirects(false);
        $client->request(
            'GET',
            '/account/thirdpartyauth/linkedin/return'
        );
        $client->followRedirect();

        // Verifies that at this point, the asset generation async message has been dispatched
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

        $client->followRedirect();
        $crawler = $client->followRedirect();

        $this->assertSelectorTextSame(
            'title',
            'Fyyn — Videos'
        );

        $this->assertSelectorTextNotContains(
            'body',
            'Your email address has been verified.'
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
