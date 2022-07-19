<?php

namespace App\Controller\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionVideoChunk;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function getRecordingSessionVideoChunkBlobAction(
        string $recordingSessionId,
        string $videoChunkId,
        EntityManagerInterface $entityManager
    ): Response {

        $videoChunk = $entityManager->find(RecordingSessionVideoChunk::class, $videoChunkId);

        if (is_null($videoChunk)) {
            throw new NotFoundHttpException("No video chunk with id '$videoChunkId'.");
        }

        if ($videoChunk->getRecordingSession()->getId() !== $recordingSessionId) {
            throw new BadRequestHttpException("recording session id of video chunk is not '$recordingSessionId'.");
        }

        $response = new StreamedResponse();
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Content-Type' , $videoChunk->getMimeType());

        $response->setCallback(function () use ($videoChunk) {
            print(stream_get_contents($videoChunk->getVideoBlob()));
            flush();
        });

        return $response->send();
    }

    public function getRecordingSessionFullVideoBlobAction(
        string $recordingSessionId,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response
    {
        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw new NotFoundHttpException("No recording session with id '$recordingSessionId'.");
        }

        $response = new StreamedResponse();
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Content-Type' , 'video/webm');

        $content = '';
        foreach ($recordingSession->getRecordingSessionVideoChunks() as $chunk) {
            $logger->debug("Adding chunk id '{$chunk->getId()}' with name '{$chunk->getName()}'.");
            $content .= stream_get_contents($chunk->getVideoBlob());
        }

        $response->setCallback(function () use ($content) {
            echo $content;
            flush();
        });
        return $response->send();
    }
}
