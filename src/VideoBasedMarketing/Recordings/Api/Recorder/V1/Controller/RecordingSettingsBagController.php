<?php

namespace App\VideoBasedMarketing\Recordings\Api\Recorder\V1\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Infrastructure\Enum\CookieName;
use App\VideoBasedMarketing\Recordings\Api\Recorder\V1\Entity\RecordingSettingsBag;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class RecordingSettingsBagController
    extends AbstractController
{
    #[Route(
        path        : '%app.routing.route_prefix.api%/recorder/v1/recordings/recording-settings-bag',
        name        : 'videobasedmarketing.recordings.api.recorder.v1.recording-settings-bag.get',
        methods     : [Request::METHOD_GET]
    )]
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

    #[Route(
        path        : '%app.routing.route_prefix.api%/recorder/v1/recordings/recording-settings-bag',
        name        : 'videobasedmarketing.recordings.api.recorder.v1.recording-settings-bag.set',
        methods     : [Request::METHOD_POST]
    )]
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
}
