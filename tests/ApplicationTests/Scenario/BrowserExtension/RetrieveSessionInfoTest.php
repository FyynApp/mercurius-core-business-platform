<?php

namespace App\Tests\ApplicationTests\Scenario\BrowserExtension;

use App\Tests\ApplicationTests\Helper\BrowserExtensionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class RetrieveSessionInfoTest
    extends WebTestCase
{
    public function testUserLanguageDependsOnUiLanguage(): void
    {
        $client = static::createClient();

        BrowserExtensionHelper::getSessionInfo($client);
        $content = $client->getResponse()->getContent();
        $parsedContent = json_decode($content, true);

        $this->assertSame(
            'en',
            $parsedContent['settings']['userLanguage']
        );

        $client->request(
            'GET',
            '/de/benutzerkonto/einloggen'
        );

        BrowserExtensionHelper::getSessionInfo($client);
        $content = $client->getResponse()->getContent();
        $parsedContent = json_decode($content, true);

        $this->assertSame(
            'de',
            $parsedContent['settings']['userLanguage']
        );
    }
}
