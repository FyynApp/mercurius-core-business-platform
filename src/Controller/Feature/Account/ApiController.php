<?php

namespace App\Controller\Feature\Account;

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ApiController extends AbstractController
{
    public function getExtensionSessionInfoAction(Request $request): Response
    {
        return new Response();
    }
}
