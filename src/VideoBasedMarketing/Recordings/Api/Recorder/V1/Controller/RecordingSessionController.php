<?php

namespace App\VideoBasedMarketing\Recordings\Api\Recorder\V1\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class RecordingSessionController
    extends AbstractController
{
    #[Route(
        path        : '%app.routing.route_prefix.api%/recorder/v1/recordings/recording-sessions/{recordingSessionId}/info',
        name        : 'videobasedmarketing.recordings.api.recorder.v1.recording_session.info',
        methods     : [Request::METHOD_GET]
    )]
    public function getRecordingSessionInfoAction(
        string                 $recordingSessionId,
        EntityManagerInterface $entityManager,
        RouterInterface        $router
    ): JsonResponse
    {
        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw $this->createNotFoundException("A recording session with id '$recordingSessionId' does not exist.");
        }

        $this->denyAccessUnlessGranted(AccessAttribute::Use->value, $recordingSession);

        $user = $recordingSession->getUser();

        $responseBody = [
            'settings' => [
                'status' => Response::HTTP_OK,
                'sessionId' => $recordingSessionId,
                'userId' => $user->getId(),
                'userName' => $user->getUserIdentifier(),
                'staffapply' => [
                    'logo' => [
                        'src' => ''
                    ]
                ],
                'brand' => [
                    'brandPrimaryColor' => '#ffffff',
                    'brandBtnBgColor' => '#02A1E8',
                    'brandBtnTextColor' => '#ffffff'
                ],
                'formBgColor' => '#02A1E8',
                'validSession' => 1,
                'copyright' => false,
                'trackingUrl' => 'https://eu-cnt.staffapply.com/',
                'applicant' => [
                    'title' => 'Dr.',
                    'salutation' => 'Mr.',
                    'prename' => 'PreName',
                    'surname' => 'SurName'
                ]
            ],

            'mediaAlert' => [
                'xnotAllowed' => '<strong>Zugriff verweigert</strong> Bitte erlaube den Zugriff auf deine Kamera und Mikrofon.',
                'info' => '<strong>Wichtig!!</strong> Bitte aktiviere deine Kamera.',
                'notSupported' => '<strong>Dein Gerät wird nicht unterstützt.</strong>'
            ],

            'videoInterview' => [
                'post' => [
                    'ios_upload_url' => 'https://c1.staffapply.com/iOS_upload.php',
                    'mediaPostUrl' => $router->generate(
                        'videobasedmarketing.recordings.api.recorder.v1.recording_session.video_chunk.handle',
                        ['recordingSessionId' => $recordingSessionId],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ],
                'maxAnswerTimeText' => 'maximale Aufnahmedauer',
                'maxAnswerTime' => 300,
                'nextUrl' => $router->generate(
                    'videobasedmarketing.recordings.presentation.return_from_recording_studio',
                    ['recordingSessionId' => $recordingSessionId],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'recordSuccessText' => 'Dein Video wird gespeichert... Es dauert nicht lange, versprochen!',
                'recordBtnText' => 'Aufnahme starten',
                'stopBtnText' => 'Aufnahme beenden',
                'finished' => [
                    'nextBtn' => [
                        'text' => 'weiter zum nächsten Schritt'
                    ],
                    'againBtn' => [
                        'text' => 'Aufnahme wiederholen'
                    ],
                    'waitingBtn' => [
                        'text' => 'verarbeite...'
                    ],
                    'workingtext' => 'Cooles Video - wir benötigen noch einige Sekunden, wir sind gleich fertig...',
                    'titletext' => 'Dein geniales Video ist bereit um die Welt zu erobern!',
                    'headline' => 'Aufnahme beendet',
                    'subheadline' => 'Bist du mit der Aufnahme zufrieden?',
                    'text' => 'Bist du mit Ton und Video zufrieden?\nWenn du mit der Aufnahmen nicht zufrieden bist, kannst du die Aufnahme wiederholen.',
                    'notWorkingBtn' => [
                        'text' => 'not working (Error)-'
                    ]
                ],
                'functionTest' => [
                    'headline' => 'Kamera- und Toneinstellungen',
                    'subheadline' => 'Funktionstest',
                    'text' => 'Bitte kontrolliere ob die Kamera und das Mikrofon richtig konfiguriert sind.\n\nWähle bei Bedarf die richtige Kamera und das richtige Mikrofon.\n\n<strong>Wichtig:</strong> Dein Videobild muss sichtbar sein.',
                    'microphoneText' => 'Dein Mikrofon',
                    'cameraText' => 'Dein Kamera',
                    'noDevicePermissionText' => 'Leider kann nicht auf deine Kamera zugeriffen werden.\nBitte aktiviere die Kamera in deinem Browser.',
                    'callToActionText' => '',
                    'btn' => [
                        'text' => 'Weiter zum Test der Aufnahmefunktion',
                        'path' => '/sample-question'
                    ]
                ],
                'recordingTest' => [
                    'heroPriority' => '',
                    'video' => [
                        'src' => ''
                    ],
                    'heroImage' => [
                        'src' => ''
                    ],
                    'headline' => 'Aufnahme Test',
                    'subheadline' => '',
                    'text' => 'In diesem Schritt wird die Aufnahmefunktion deiner Kamera überpüft.\n\nBitte starte die Aufnahme und erstellen ein kurzes <strong>Test-Video</strong> in dem du z.B. deinen Namen sagst.\nNach der Aufnahme prüfe bitte ob der <strong>Ton</strong> hörbar und das <strong>Video</strong> sichtbar ist.\n<strong>Zum starten der Aufnahme</strong>, kicke den Button unterhalb des Videobildes.\nDie Aufnahme wird automatich nach 5 Sekunden beendet.\n<strong>Hinweis:</strong> Dieses Video ist <u>kein</u> Teil der Bewerbung.'
                ]
            ],

            'notFound' => [
                'staffapply' => [
                    'logo' => [
                        'src' => 'https://static.staffapply.com/Logo-StaffApplyv2.png'
                    ]
                ],
                'headline' => 'This is a Headline',
                'text' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr,\n\nsed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat,\nsed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.\n\nStet clita kasd gubergren.'
            ]
        ];

        return $this->json(
            $responseBody,
            Response::HTTP_OK
        );
    }

    #[Route(
        path        : '%app.routing.route_prefix.api%/recorder/v1/recordings/recording-sessions/{recordingSessionId}/video-chunks/',
        name        : 'videobasedmarketing.recordings.api.recorder.v1.recording_session.video_chunk.handle',
        methods     : [Request::METHOD_POST]
    )]
    public function handleRecordingSessionVideoChunkAction(
        string                          $recordingSessionId,
        Request                         $request,
        RouterInterface                 $router,
        RecordingsInfrastructureService $recordingSessionInfrastructureService,
        EntityManagerInterface          $entityManager
    ): Response
    {
        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw $this->createNotFoundException("Could not find recording session with id '$recordingSessionId'.");
        }

        $this->denyAccessUnlessGranted(AccessAttribute::Use->value, $recordingSession);

        $user = $this->getUser();

        if (!is_null($request->get('recordingDone'))
            && (string)$request->get('recordingDone') === 'true'
        ) {
            $recordingSessionInfrastructureService->handleDoneChunkArrived(
                $recordingSession
            );

            return $this->json(
                [
                    'status' => Response::HTTP_OK,
                    'preview' => $router->generate(
                        'videobasedmarketing.recordings.presentation.recording_session.recording_preview.poster.asset',
                        [
                            'recordingSessionId' => $recordingSessionId,
                            'extension' => $recordingSessionInfrastructureService
                                ->mimeTypeToFileSuffix(AssetMimeType::ImageWebp)
                        ]
                    ),

                    // We receive the 'recordingDone' request BEFORE the final video chunk is received.
                    // This creates a chicken-and-egg problem: We need to return the recording preview asset
                    // url for the recordingDone request, but only the next video chunk request handling (which
                    // follows only after the 'recordingDone' request) can actually create the full video preview
                    // (because only at this point do we have all the chunks).
                    // Thus, we do not return the actual asset url here - instead, we return the url to a
                    // controller action which will wait until the recording preview asset has been generated,
                    // and redirects to the actual asset url afterwards.
                    'previewVideo' => $router->generate(
                        'videobasedmarketing.recordings.presentation.recording_session.recording_preview.asset_redirect',
                        [
                            'recordingSessionId' => $recordingSessionId,
                            'random' => bin2hex(random_bytes(8))
                        ]
                    )
                ]
            );

        } else {
            $chunkName = $request->get('video');

            if (is_null($chunkName)) {
                throw new BadRequestHttpException("Missing request value 'video'.");
            }

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('video-blob');

            if (is_null($uploadedFile)) {
                throw new BadRequestHttpException("Missing request file part 'video-blob'.");
            }

            $recordingSessionInfrastructureService->handleRecordingSessionVideoChunk(
                $recordingSession,
                $user,
                $chunkName,
                $uploadedFile->getPathname(),
                $uploadedFile->getMimeType()
            );

            return $this->json(
                [
                    'status' => Response::HTTP_OK
                ]
            );
        }
    }
}
