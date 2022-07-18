<?php

namespace App\Controller\Feature\Recordings;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordingsController extends AbstractController
{
    public function returnFromRecordingSessionAction(Request $request): Response
    {
        return new Response($request->get('recordingSessionId'));
    }
}
