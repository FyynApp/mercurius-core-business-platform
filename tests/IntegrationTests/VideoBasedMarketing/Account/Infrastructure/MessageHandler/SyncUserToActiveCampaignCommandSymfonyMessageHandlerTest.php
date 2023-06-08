<?php

namespace App\Tests\IntegrationTests\VideoBasedMarketing\Account\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\ActiveCampaignContactTag;
use App\VideoBasedMarketing\Account\Infrastructure\SymfonyMessage\SyncUserToActiveCampaignCommandSymfonyMessage;
use App\VideoBasedMarketing\Account\Infrastructure\SymfonyMessageHandler\SyncUserToActiveCampaignCommandSymfonyMessageHandler;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SyncUserToActiveCampaignCommandSymfonyMessageHandlerTest
    extends KernelTestCase
{
    /**
     * @throws TransportExceptionInterface
     */
    public function test(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);

        /** @var null|User $user */
        $user = $userRepository->findOneBy(['email' => RegisteredUserFixture::EMAIL]);

        /** @var SyncUserToActiveCampaignCommandSymfonyMessageHandler $handler */
        $handler = $container->get(SyncUserToActiveCampaignCommandSymfonyMessageHandler::class);

        $handler(
            new SyncUserToActiveCampaignCommandSymfonyMessage(
                $user,
                [ActiveCampaignContactTag::RegisteredThroughTheChromeExtension]
            )
        );

        $this->assertSame(
            1,
            $user->getActiveCampaignContact()->getId()
        );
    }
}
