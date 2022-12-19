<?php

namespace App\Tests\IntegrationTests\VideoBasedMarketing\Account\Infrastructure\Service;

use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ActiveCampaignService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class ActiveCampaignServiceTest
    extends KernelTestCase
{
    public function test(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => RegisteredUserFixture::EMAIL]);

        /** @var ActiveCampaignService $activeCampaignService */
        $activeCampaignService = $container->get(ActiveCampaignService::class);

        $contact = $activeCampaignService->createContact($user);

        $this->assertSame(1, $contact->getId());
    }
}
