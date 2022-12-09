<?php

namespace App\Tests\Application\Feature\BrowserExtension;

use App\Tests\Application\Helper\BrowserExtension\RecordingSessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class UnregisteredUserWorkflowTest
    extends WebTestCase
{
    public function test(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        RecordingSessionHelper::createRecordingSession($client);

        $structuredResponse = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $postUrl = mb_ereg_replace('http://localhost', '', $structuredResponse['postUrl']);

        $client->request(
            'POST',
            $postUrl,
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );

        print_r($structuredResponse);
    }
}
