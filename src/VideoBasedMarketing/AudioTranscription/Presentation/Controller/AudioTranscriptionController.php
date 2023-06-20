<?php

namespace App\VideoBasedMarketing\AudioTranscription\Presentation\Controller;


use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    #[Route(
        path   : 'audio-transcriptions/web-vtt/{webVttId}/editor',
        name   : 'videobasedmarketing.audio_transcription.presentation.web_vtt.editor',
        methods: [Request::METHOD_GET]
    )]
    public function showWebVttEditorAction(
        string $webVttId
    ): Response
    {
        $r = $this->verifyAndGetOrganizationAndEntity(
            AudioTranscriptionWebVtt::class,
            $webVttId,
            AccessAttribute::Edit
        );

        /** @var AudioTranscriptionWebVtt $webVtt */
        $webVtt = $r->getEntity();

        return $this->render(
            '@videobasedmarketing.audio_transcription/web_vtt_editor.html.twig',
            ['webVtt' => $webVtt]
        );
    }

    #[Route(
        path   : 'audio-transcriptions/web-vtt/{webVttId}',
        name   : 'videobasedmarketing.audio_transcription.presentation.web_vtt.save',
        methods: [Request::METHOD_POST]
    )]
    public function saveWebVttAction(
        string                 $webVttId,
        TranslatorInterface    $translator,
        Request                $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $r = $this->verifyAndGetOrganizationAndEntity(
            AudioTranscriptionWebVtt::class,
            $webVttId,
            AccessAttribute::Edit
        );

        /** @var AudioTranscriptionWebVtt $webVtt */
        $webVtt = $r->getEntity();

        if (!$this->isCsrfTokenValid(
            'edit-web-vtt-' . $webVtt->getId(),
            $request->get('_csrf_token'))
        ) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $webVtt->setVttContent($request->get('web_vtt_content'));
        $entityManager->persist($webVtt);
        $entityManager->flush();

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $translator->trans(
                'edit_web_vtt.sucess',
                [],
                'videobasedmarketing.audio_transcription'
            )
        );

        return $this->redirectToRoute(
            'videobasedmarketing.audio_transcription.presentation.web_vtt.editor',
            ['webVttId' => $webVtt->getId()]
        );
    }
}
