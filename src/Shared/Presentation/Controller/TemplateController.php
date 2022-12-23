<?php

namespace App\Shared\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class TemplateController
    extends AbstractController
{
    public function previewAction(
        Request         $request
    ): Response
    {
        return $this->render(
            $request->get('template'),
            $request->get('parameters', [])
        );
    }
}
