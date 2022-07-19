<?php

namespace App\Controller\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionFullVideo;
use App\Entity\Feature\Recordings\RecordingSessionVideoChunk;
use App\Service\Feature\Recordings\RecordingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;

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

    public function returnFromRecordingSessionAction(): Response
    {
        return $this->redirectToRoute('feature.recordings.recording_sessions.overview');
    }

    public function recordingSessionsOverviewAction(RecordingsService $recordingsService): Response
    {
        return $this->render(
            'feature/recordings/recording_sessions_overview.html.twig',
            ['RecordingsService' => $recordingsService]
        );
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
        Filesystem $filesystem,
        ?Profiler $profiler
    ): Response {

        ini_set('memory_limit', '1024M');
        if (!is_null($profiler)) {
            $profiler->disable();
        }
        $entityManager->getConfiguration()->setSQLLogger();

        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw new NotFoundHttpException("No recording session with id '$recordingSessionId'.");
        }

        $response = new StreamedResponse();
        $response->headers->set('X-Accel-Buffering', 'no');

        if (is_null($recordingSession->getRecordingSessionFullVideo())) {
            $fullVideo = new RecordingSessionFullVideo();
            $fullVideo->setRecordingSession($recordingSession);
            $fullVideo->setMimeType($recordingSession->getRecordingSessionVideoChunks()->first()->getMimeType());

            $response->headers->set('Content-Type' , $fullVideo->getMimeType());

            $workdirPath = '/var/tmp/mercurius-core-business-platform/' . sha1(rand(0, PHP_INT_MAX));

            $filesystem->mkdir($workdirPath);

            $tmpFilePaths = [];
            foreach ($recordingSession->getRecordingSessionVideoChunks() as $chunk) {
                $tmpFilePath = $filesystem->tempnam($workdirPath, $chunk->getId(), '.webm');
                file_put_contents($tmpFilePath, stream_get_contents($chunk->getVideoBlob()));
                $tmpFilePaths[] = $tmpFilePath;
            }

            $filelistFileContent = '';
            foreach ($tmpFilePaths as $tmpFilePath) {
                $filelistFileContent .= "file '$tmpFilePath'\n";
            }
            $filelistFilePath = $filesystem->tempnam($workdirPath, 'filelist');
            file_put_contents($filelistFilePath, $filelistFileContent);

            $resultFilePath = $workdirPath . '/' . 'result.webm';

            shell_exec("/opt/homebrew/bin/ffmpeg -f concat -safe 0 -i $filelistFilePath -c copy $resultFilePath");

            $fileResource = fopen($resultFilePath, 'r');
            $fullVideo->setVideoBlob(stream_get_contents($fileResource));
            fclose($fileResource);

            $entityManager->persist($fullVideo);
            $entityManager->flush();

            /*
            foreach ($recordingSession->getRecordingSessionVideoChunks() as $chunk) {
                $entityManager->remove($chunk);
            }
            $entityManager->flush();
            */

            $response->setCallback(function () use ($resultFilePath, $filesystem, $workdirPath) {
                $fileResource = fopen($resultFilePath, 'r');
                print stream_get_contents($fileResource);
                flush();
                fclose($fileResource);
                $filesystem->remove($workdirPath);
            });

        } else {
            $response->headers->set('Content-Type' , $recordingSession->getRecordingSessionFullVideo()->getMimeType());
            $response->setCallback(function () use ($recordingSession) {
                print stream_get_contents($recordingSession->getRecordingSessionFullVideo()->getVideoBlob());
                flush();
            });
        }

        return $response->send();
    }
}
