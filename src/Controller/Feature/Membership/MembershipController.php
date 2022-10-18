<?php

namespace App\Controller\Feature\Membership;

use App\Service\Feature\Membership\MembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class MembershipController extends AbstractController
{
    public function overviewAction(MembershipService $membershipService): Response
    {
        return new Response($membershipService->getMembershipPlanForUser($this->getUser())->getName()->value);
    }
}
