<?php

namespace App\Tests\ApplicationTests\Scenario\Recordings;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

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

        $this->assertSelectorTextSame('[data-test-id="video-folder-0"]', 'Testfolder 0 videos');
    }

    public function testFolderVisibility(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);

        /** @var null|User $user */
        $user = $userRepository->findOneBy(['email' => RegisteredExtensionOnlyUserFixture::EMAIL]);

        $client->loginUser($user);

        $crawler = $client->request(
            Request::METHOD_GET,
            '/en/my/recordings/videos/'
        );

        $createFolderButton = $crawler->selectButton('Create folder');
        $form = $createFolderButton->form();
        $form['name'] = 'Testfolder';
        $client->submit($form);
        $client->followRedirect();

        $client->clickLink('Testfolder 0 videos');

        $entityManager = $container->get(EntityManagerInterface::class);
        $videoFoldersRepository = $entityManager->getRepository(VideoFolder::class);

        /** @var null|VideoFolder $videoFolder */
        $videoFolder = $videoFoldersRepository->findOneBy(['name' => 'Testfolder']);

        $this->assertSelectorTextSame(
            "[data-test-id=\"video-folder-{$videoFolder->getId()}-visibility-for-non-administrators-cta\"]",
            'Visibility: all'
        );

        $videoFolder->setIsVisibleForNonAdministrators(false);
        $entityManager->persist($videoFolder);
        $entityManager->flush();

        $client->reload();

        $this->assertSelectorTextSame(
            "[data-test-id=\"video-folder-{$videoFolder->getId()}-visibility-for-non-administrators-cta\"]",
            'Visibility: Admins only'
        );
    }
}
