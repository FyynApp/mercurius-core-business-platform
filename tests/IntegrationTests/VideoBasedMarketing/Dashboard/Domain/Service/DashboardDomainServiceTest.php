<?php

namespace App\Tests\IntegrationTests\VideoBasedMarketing\Dashboard\Domain\Service;

use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Dashboard\Domain\Service\DashboardDomainService;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class DashboardDomainServiceTest
    extends KernelTestCase
{
    public function test(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => RegisteredUserFixture::EMAIL]);

        $dashboardService = $container->get(DashboardDomainService::class);

        $this->assertEquals(
            0,
            $dashboardService->getNumberOfPresentationpages(
                $user,
                PresentationpageType::Page
            )
        );
    }
}
