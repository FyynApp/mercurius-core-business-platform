<?php

namespace App\Tests\IntegrationTests\VideoBasedMarketing\Account\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\ActiveCampaignContactTag;
use App\VideoBasedMarketing\Account\Infrastructure\Message\SyncUserToActiveCampaignCommandMessage;
use App\VideoBasedMarketing\Account\Infrastructure\MessageHandler\SyncUserToActiveCampaignCommandMessageHandler;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SyncUserToActiveCampaignCommandMessageHandlerTest
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

        /** @var SyncUserToActiveCampaignCommandMessageHandler $handler */
        $handler = $container->get(SyncUserToActiveCampaignCommandMessageHandler::class);

        $handler(
            new SyncUserToActiveCampaignCommandMessage(
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
