<?php

namespace App\Controller\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Repository\Feature\Account\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RecordingsApiController extends AbstractController
{
    public function getRecordingSessionInfoAction(
        string $recordingSessionId,
        UserRepository $userRepository,
        RouterInterface $router
    ): JsonResponse {
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => 'manuel@kiessling.net']);

        if (is_null($user)) {
            return $this->json(
                'Not an authorized user.',
                Response::HTTP_FORBIDDEN,
                [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000'
                ]
            );
        }

        $responseBody = [
            'settings' => [
                'status' => 200,
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
                    'mediaPostUrl' => 'https://c1.staffapply.com/slashvid.php'
                ],
                'maxAnswerTimeText' => 'maximale Aufnahmedauer',
                'maxAnswerTime' => 300,
                'nextUrl' => $router->generate(
                    'feature.recordings.return_from_recording_session',
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
            Response::HTTP_OK,
            [
                'Access-Control-Allow-Origin' => 'http://localhost:3000'
            ]
        );
    }

}
