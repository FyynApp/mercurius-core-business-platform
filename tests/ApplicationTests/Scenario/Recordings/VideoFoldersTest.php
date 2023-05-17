<?php

namespace App\Tests\ApplicationTests\Scenario\Recordings;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VideoFoldersTest
    extends WebTestCase
{
    public function testFolderCreation(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);

        /** @var null|User $user */
        $user = $userRepository->findOneBy(['email' => RegisteredExtensionOnlyUserFixture::EMAIL]);

        $client->loginUser($user);

        $crawler = $client->request(
            'GET',
            '/en/my/recordings/videos/'
        );

        $createFolderButton = $crawler->selectButton('Create folder');
        $form = $createFolderButton->form();
        $form['name'] = 'Testfolder';
        $client->submit($form);
        $client->followRedirect();

        $this->assertSelectorTextSame('[data-test-id="video-folder-0"]', "Testfolder 0 videos");
    }
}
