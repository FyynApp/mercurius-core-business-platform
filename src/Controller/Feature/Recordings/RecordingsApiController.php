<?php

namespace App\Controller\Feature\Recordings;

use App\Controller\AbstractController;
use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\AssetMimeType;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSettingsBag;
use App\Security\VotingAttribute;
use App\Service\Aspect\Cookies\CookieName;
use App\Service\Feature\Recordings\RecordingSessionService;
use App\Service\Feature\Recordings\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class RecordingsApiController extends AbstractController
{
    public function getRecordingSessionInfoAction(
        string                 $recordingSessionId,
        EntityManagerInterface $entityManager,
        RouterInterface        $router
    ): JsonResponse
    {
        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw new NotFoundHttpException("A recording session with id '$recordingSessionId' does not exist.");
        }

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
                'notAllowed' => '<strong>Zugriff verweigert</strong> Bitte erlauben Sie den Zugriff auf Ihre Kamera und Mikrofon.',
                'info' => '<strong>Wichtig!!</strong> Bitte aktivieren Sie Ihre Kamera.',
                'notSupported' => '<strong>Ihr Gerät wird nicht unterstützt.</strong>'
            ],

            'videoInterview' => [
                'post' => [
                    'ios_upload_url' => 'https://c1.staffapply.com/iOS_upload.php',
                    'mediaPostUrl' => $router->generate(
                        'api.feature.recordings.recording_session.handle_video_chunk',
                        ['recordingSessionId' => $recordingSessionId],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ],
                'maxAnswerTimeText' => 'maximale Aufnahmedauer',
                'maxAnswerTime' => 300,
                'nextUrl' => $router->generate(
                    'feature.recordings.return_from_recording_studio',
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
                    'subheadline' => 'Sind Sie mit der Aufnahme zufrieden?',
                    'text' => 'Sind Sie mit Ton und Video zufrieden?\nWenn Sie mit der Aufnahmen nicht zufrieden sind, können Sie die Aufnahmen wiederholen.',
                    'notWorkingBtn' => [
                        'text' => 'not working (Error)-'
                    ]
                ],
                'functionTest' => [
                    'headline' => 'Kamera- und Toneinstellungen',
                    'subheadline' => 'Funktionstest',
                    'text' => 'Bitte kontrollieren Sie ob die Kamera und das Mikrofon richtig konfiguriert sind.\n\nWählen Sie bei Bedarf die richtige Kamera und das richtige Mikrofon.\n\n<strong>Wichtig:</strong> Ihr Videobild muss sichtbar sein.',
                    'microphoneText' => 'Ihr Mikrofon',
                    'cameraText' => 'Ihre Kamera',
                    'noDevicePermissionText' => 'Leider kann nicht auf Ihre Kamera zugeriffen werden.\nBitte aktivieren Sie die Kamera in Ihrem Brow',
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
                    'text' => 'In diesem Schritt wird die Aufnahmefunktion Ihrer Kamera überpüft.\n\nBitte starten Sie die Aufnahme und erstellen ein kurzes <strong>Test-Video</strong> in dem Sie z.B. Ihren Namen sagen.\nNach der Aufnahme prüfen Sie bitte ob der <strong>Ton</strong> hörbar und das <strong>Video</strong> sichtbar ist.\n<strong>Zum starten der Aufnahme</strong>, kicken Sie den Button unterhalb des Videobildes.\nDie Aufnahme wird automatich nach 5 Sekunden beendet.\n<strong>Hinweis:</strong> Dieses Video ist <u>kein</u> Teil der Bewerbung.'
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


    public function getRecordingSettingsBagAction(
        Request                $request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $clientId = $request->cookies->get(CookieName::ClientId->value);

        /** @var EntityRepository $repo */
        $repo = $entityManager->getRepository(RecordingSettingsBag::class);

        /** @var ?RecordingSettingsBag $settingsBag */
        $settingsBag = $repo->findOneBy(['clientId' => $clientId]);

        if (is_null($settingsBag)) {
            $responseBody = [
                'status' => Response::HTTP_OK,
                'userSessionId' => $request->get('userSessionId'),
                'audio' => true,
                'video' => true
            ];
        } else {
            $settings = json_decode($settingsBag->getSettings(), true);
            $responseBody = [
                'status' => Response::HTTP_OK,
                'userSessionId' => $request->get('userSessionId'),
                'audio' => $settings['audio'],
                'video' => $settings['video']
            ];
        }

        return $this->json($responseBody);
    }


    public function setRecordingSettingsBagAction(
        Request                $request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $clientId = $request->cookies->get(CookieName::ClientId->value);

        /** @var EntityRepository $repo */
        $repo = $entityManager->getRepository(RecordingSettingsBag::class);

        /** @var ?RecordingSettingsBag $settingsBag */
        $settingsBag = $repo->findOneBy(['clientId' => $clientId]);

        if (is_null($settingsBag)) {
            $settingsBag = new RecordingSettingsBag($this->getUser());
            $settingsBag->setClientId($clientId);
        }

        $content = $request->getContent();

        $contentAsArray = json_decode($content, true);

        $settingsBag->setSettings(
            json_encode(
                [
                    'audio' => $contentAsArray['audio'],
                    'video' => $contentAsArray['video']
                ]
            )
        );

        $entityManager->persist($settingsBag);
        $entityManager->flush();

        return $this->json(['status' => Response::HTTP_OK]);
    }


    public function handleRecordingSessionVideoChunkAction(
        string                  $recordingSessionId,
        Request                 $request,
        RouterInterface         $router,
        RecordingSessionService $recordingSessionService,
        EntityManagerInterface  $entityManager,
        LoggerInterface         $logger,
        VideoService            $videoService
    ): Response
    {
        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw new NotFoundHttpException("Could not find recording session with id '$recordingSessionId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $recordingSession);

        /** @var User $user */
        $user = $this->getUser();

        if (!is_null($request->get('recordingDone'))
            && (string)$request->get('recordingDone') === 'true'
        ) {
            $recordingSessionService->handleRecordingDone($recordingSession);

            return $this->json(
                [
                    'status' => Response::HTTP_OK,
                    'preview' => $router->generate(
                        'feature.recordings.recording_session.recording_preview.poster.asset',
                        [
                            'recordingSessionId' => $recordingSessionId,
                            'extension' => $videoService->mimeTypeToFileSuffix(AssetMimeType::ImageWebp)
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
                        'feature.recordings.recording_session.recording_preview.asset-redirect',
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

            $logger->info('Dies ist ein Test');
            $logger->warning('Dies ist ein Test');
            $logger->info("Uploaded file path is '{$uploadedFile->getPathname()}'.");

            $recordingSessionService->handleRecordingSessionVideoChunk(
                $recordingSession,
                $user,
                $chunkName,
                $uploadedFile->getPathname(),
                $uploadedFile->getMimeType(),
                $videoService
            );

            return $this->json(
                [
                    'status' => Response::HTTP_OK
                ]
            );
        }
    }
}
