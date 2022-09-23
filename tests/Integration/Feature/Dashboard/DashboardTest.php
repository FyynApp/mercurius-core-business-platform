<?php

namespace App\Tests\Integration\Feature\Dashboard;

use App\DataFixtures\Feature\Account\UserFixture;
use App\Entity\Feature\Presentationpages\PresentationpageType;
use App\Repository\Feature\Account\UserRepository;
use App\Service\Feature\Dashboard\DashboardService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class DashboardTest extends KernelTestCase
{
    public function test ()
    {
        self::bootKernel();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => UserFixture::TEST_USER_EMAIL]);

        $dashboardService = $container->get(DashboardService::class);

        $this->assertEquals(
            0,
            $dashboardService->getNumberOfPresentationpages(
                $user,
                PresentationpageType::Page
            )
        );
    }
}
