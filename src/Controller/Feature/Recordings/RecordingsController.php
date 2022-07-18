<?php

namespace App\Controller\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordingsController extends AbstractController
{
    public function recordingStudioAction(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $recordingSession = new RecordingSession();
        $recordingSession->setUser($user);
        $entityManager->persist($recordingSession);
        $entityManager->flush($recordingSession);

        return $this->render(
            'feature/recordings/recording_studio.html.twig',
            ['recordingSession' => $recordingSession]
        );
    }

    public function returnFromRecordingSessionAction(Request $request): Response
    {
        return new Response($request->get('recordingSessionId'));
    }
}
