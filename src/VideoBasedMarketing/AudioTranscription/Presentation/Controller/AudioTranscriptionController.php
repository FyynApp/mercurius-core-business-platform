<?php

namespace App\VideoBasedMarketing\AudioTranscription\Presentation\Controller;


use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AudioTranscriptionController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/audio-transcriptions/{audioTranscriptionId}',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/audio-abschriften/{audioTranscriptionId}',
        ],
        name        : 'videobasedmarketing.audio_transcription.presentation.overview',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function overviewAction(
        string $audioTranscriptionId
    ): Response
    {
        $r = $this->verifyAndGetOrganizationAndEntity(
            AudioTranscription::class,
            $audioTranscriptionId,
            AccessAttribute::Edit
        );

        return $this->render(
            '@videobasedmarketing.audio_transcription/overview.html.twig',
            ['audioTranscription' => $r->getEntity()]
        );
    }
}
