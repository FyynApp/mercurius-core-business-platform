<?php

namespace App\Controller\Feature\Membership;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class MembershipController extends AbstractController
{
    public function overviewAction(): Response
    {
        return new Response();
    }
}
