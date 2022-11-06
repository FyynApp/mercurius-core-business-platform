<?php

namespace App\Tests\Integration\Feature\Dashboard;

use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\UserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class DashboardTest
    extends KernelTestCase
{
    public function test()
    {
        self::bootKernel();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => UserFixture::TEST_USER_EMAIL]);

        $dashboardService = $container->get(\App\VideoBasedMarketing\Dashboard\Domain\Service\DashboardService::class);

        $this->assertEquals(
            0,
            $dashboardService->getNumberOfPresentationpages(
                $user,
                PresentationpageType::Page
            )
        );
    }
}
