<?php

namespace App\Tests\ApplicationTests\Feature\BrowserExtension;

use App\Tests\ApplicationTests\Helper\BrowserExtensionHelper;
use App\Tests\ApplicationTests\Helper\RecordingSessionHelper;
use App\VideoBasedMarketing\Account\Infrastructure\Message\SyncUserToActiveCampaignCommandMessage;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;


class UnregisteredUserWorkflowTest
    extends WebTestCase
{
    use MailerAssertionsTrait;

    public function test(): void
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


        $client->followRedirects();
        $crawler = $client->request(
            'GET',
            $recordingSessionFinishedTargetUrl
        );

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

        $this->assertEmailCount(1);

        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'Welcome');
        $this->assertEmailHtmlBodyContains($email, 'foo@bar.de');

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
    }
}
