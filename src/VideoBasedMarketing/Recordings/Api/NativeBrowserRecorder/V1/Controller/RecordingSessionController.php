<?php

namespace App\VideoBasedMarketing\Recordings\Api\NativeBrowserRecorder\V1\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionDomainService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class RecordingSessionController
    extends AbstractController
{
    #[Route(
        path        : '%app.routing.route_prefix.api%/native-browser-recorder/v1/recordings/recording-sessions/',
        name        : 'videobasedmarketing.recordings.api.native_browser_recorder.v1.create_recording_session',
        methods     : [Request::METHOD_POST]
    )]
    public function createRecordingSessionAction(
        RecordingSessionDomainService $recordingSessionDomainService,
        RouterInterface               $router
    ): Response
    {
        $user = $this->getUser();

        if (is_null($user)) {
            return new JsonResponse(
                'This requires an active session on fyyn.io in this browser.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $recordingSession = $recordingSessionDomainService
            ->startRecordingSession($user);

        $responseContent = [
            'settings' => [
                'serviceAvailable' => true,

                'recordingSessionId' => $recordingSession->getId(),

                'postUrl' => $router->generate(
                    'videobasedmarketing.recordings.api.native_browser_recorder.v1.recording_session.handle_video_chunk',
                    ['recordingSessionId' => $recordingSession->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),

                'postChunkSize' => 5,
                'maxRecordingTime' => $recordingSessionDomainService->getMaxRecordingTime($user),
                'videoBitsPerSecond' => 1250000, // 0.15 MiB, 5-second chunks are ~1.2 MiB. See https://developers.google.com/media/vp9/settings/vod#recommended_settings
                'audioBitsPerSecond' => 64000,

                'recordingSessionRemoveUrl' => $router->generate(
                    'videobasedmarketing.recordings.api.native_browser_recorder.v1.remove_recording_session',
                    ['recordingSessionId' => $recordingSession->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),

                'shareUrl' => $router->generate(
                    'videobasedmarketing.recordings.presentation.recording_session.share',
                    [
                        'recordingSessionShortId' => $recordingSession->getShortId()
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),

                'recordingSessionFinishedTargetUrl' => $router->generate(
                    'videobasedmarketing.recordings.presentation.recording_session.finished',
                    ['recordingSessionId' => $recordingSession->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),

                'recordingSessionChunkReportUrl' => $router->generate(
                    'videobasedmarketing.recordings.api.native_browser_recorder.v1.store_chunk_report',
                    ['recordingSessionId' => $recordingSession->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]
        ];

        return new JsonResponse(
            $responseContent,
            Response::HTTP_CREATED
        );
    }

    #[Route(
        path        : '%app.routing.route_prefix.api%/native-browser-recorder/v1/recordings/recording-sessions/{recordingSessionId}/video-chunks/',
        name        : 'videobasedmarketing.recordings.api.native_browser_recorder.v1.recording_session.handle_video_chunk',
        methods     : [Request::METHOD_POST]
    )]
    public function handleRecordingSessionVideoChunkAction(
        string                          $recordingSessionId,
        Request                         $request,
        RouterInterface                 $router,
        RecordingsInfrastructureService $recordingSessionInfrastructureService,
        LoggerInterface                 $logger
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            RecordingSession::class,
            $recordingSessionId,
            AccessAttribute::Use
        );

        $user = $r->getUser();
        /** @var RecordingSession $recordingSession */
        $recordingSession = $r->getEntity();

        if (   !is_null($request->get('recordingDone'))
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
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),

                    // We (sometimes) receive the 'recordingDone' request BEFORE the final video chunk is received.
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
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
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

            if ($uploadedFile->getPathname() === '') {
                $logger->warning(
                    'Received empty video chunk.',
                    [
                        'recordingSessionId' => $recordingSessionId,
                        'chunkName' => $chunkName,
                        '$_FILES' => json_encode($_FILES)
                    ]
                );
                return $this->json(
                    [
                        'status' => Response::HTTP_OK
                    ]
                );
            }

            if (!$uploadedFile->isValid()) {
                throw new Exception('File is not valid.');
            }

            $recordingSessionInfrastructureService->handleRecordingSessionVideoChunk(
                $recordingSession,
                $user,
                $chunkName,
                $uploadedFile->getPathname(),
                $uploadedFile->getClientMimeType()
            );

            return $this->json(
                [
                    'status' => Response::HTTP_OK
                ]
            );
        }
    }

    #[Route(
        path        : '%app.routing.route_prefix.api%/native-browser-recorder/v1/recordings/recording-sessions/{recordingSessionId}',
        name        : 'videobasedmarketing.recordings.api.native_browser_recorder.v1.remove_recording_session',
        methods     : [Request::METHOD_DELETE]
    )]
    public function removeRecordingSessionAction(
        string                        $recordingSessionId,
        RecordingSessionDomainService $recordingSessionDomainService,
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            RecordingSession::class,
            $recordingSessionId,
            AccessAttribute::Delete
        );
        /** @var RecordingSession $recordingSession */
        $recordingSession = $r->getEntity();
        $recordingSessionDomainService->removeRecordingSession($recordingSession);
        return new Response('', Response::HTTP_OK);
    }

    #[Route(
        path        : '%app.routing.route_prefix.api%/native-browser-recorder/v1/recordings/recording-sessions/{recordingSessionId}/chunk-report',
        name        : 'videobasedmarketing.recordings.api.native_browser_recorder.v1.store_chunk_report',
        methods     : [Request::METHOD_PUT]
    )]
    public function storeChunkReportAction(
        RecordingSessionDomainService         $recordingSessionDomainService,
        RouterInterface                       $router
    ): Response
    {
        return new Response('', Response::HTTP_CREATED);
    }
}
