<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Controller;


use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AudioTranscriptionAssetsController
    extends AbstractController
{
    #[Route(
        path   : 'audio-transcriptions/web-vtt/{webVttId}.vtt',
        name   : 'videobasedmarketing.audio_transcription.infrastructure.web_vtt',
        methods: [Request::METHOD_GET]
    )]
    public function showWebVtt(
        string                 $webVttId,
        EntityManagerInterface $entityManager

    ): Response
    {
        /** @var null|AudioTranscriptionWebVtt $webVtt */
        $webVtt = $entityManager->find(
            AudioTranscriptionWebVtt::class,
            $webVttId
        );

        if (is_null($webVtt)) {
            throw $this->createNotFoundException("No webVtt with id '$webVttId'.");
        }

        return new Response(
            $webVtt->getVttContent(),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vtt']
        );
    }
}
