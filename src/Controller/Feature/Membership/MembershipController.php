<?php

namespace App\Controller\Feature\Membership;

use App\Entity\Feature\Account\User;
use App\Service\Feature\Membership\MembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class MembershipController extends AbstractController
{
    public function overviewAction(MembershipService $membershipService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render(
            'feature/membership/overview.html.twig',
            [
                'isSubscribed' => $membershipService->userIsSubscribed($user),
                'currentPlan' => $membershipService->getMembershipPlanForUser($user),
                'availablePlans' => $membershipService->getAvailablePlansForUser($user)
            ]
        );
    }
}
