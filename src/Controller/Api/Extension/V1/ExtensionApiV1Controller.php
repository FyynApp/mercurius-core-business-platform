<?php

namespace App\Controller\Api\Extension\V1;

use App\Entity\Feature\Recordings\AssetMimeType;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Service\Feature\Recordings\RecordingSessionService;
use App\Service\Feature\Recordings\VideoService;
use App\Shared\Presentation\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class ExtensionApiV1Controller
    extends AbstractController
{

    public function createRecordingSessionAction(
        RecordingSessionService $recordingSessionService,
        RouterInterface $router
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            return new JsonResponse(
                'Requires an active session on fyyn.io in this browser.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $recordingSession = $recordingSessionService->startRecordingSession($user);

        $responseContent = [
            'settings' => [
                'recordingSessionId' => $recordingSession->getId(),
                'postUrl' => $router->generate(
                    'api.extension.v1.recording_session.handle_video_chunk',
                    ['recordingSessionId' => $recordingSession->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'postChunkSize' => 5,
                'maxRecordingTime' => 300
            ]
        ];

        return new JsonResponse(
            $responseContent,
            Response::HTTP_CREATED
        );
    }

    public function handleRecordingSessionVideoChunkAction(
        string                  $recordingSessionId,
        Request                 $request,
        RouterInterface         $router,
        RecordingSessionService $recordingSessionService,
        EntityManagerInterface  $entityManager,
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

            $recordingSessionService->handleRecordingSessionFinished(
                $recordingSession,
                $videoService
            );

            return $this->json(
                [
                    'status' => Response::HTTP_OK,
                    'preview' => $router->generate(
                        'feature.recordings.recording_session.recording_preview.poster.asset',
                        [
                            'recordingSessionId' => $recordingSessionId,
                            'extension' => $videoService->mimeTypeToFileSuffix(AssetMimeType::ImageWebp)
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
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

            $recordingSessionService->handleRecordingSessionVideoChunk(
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
