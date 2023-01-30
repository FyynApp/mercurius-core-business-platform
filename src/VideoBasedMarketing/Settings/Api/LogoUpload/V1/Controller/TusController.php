<?php

namespace App\VideoBasedMarketing\Settings\Api\LogoUpload\V1\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Settings\Infrastructure\Service\SettingsInfrastructureService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use TusPhp\Events\UploadComplete;
use TusPhp\Tus\Server;


class TusController
    extends AbstractController
{
    #[Route(
        path: '%app.routing.route_prefix.api%/settings/logo-upload/v1/tus/',
        name: 'videobasedmarketing.settings.api.logo_upload.v1.tus_patch',
        methods: [
            Request::METHOD_POST,
            Request::METHOD_PATCH,
            Request::METHOD_HEAD
        ]
    )]
    #[Route(
        path: '%app.routing.route_prefix.api%/settings/logo-upload/v1/tus/{token?}',
        name: 'videobasedmarketing.settings.api.logo_upload.v1.tus',
        requirements: ['token' => '.+'],
        methods: [
            Request::METHOD_POST,
            Request::METHOD_PATCH,
            Request::METHOD_HEAD
        ]
    )]
    public function logoUploadTusAction(
        ?string                       $token,
        Server                        $server,
        LoggerInterface               $logger,
        SettingsInfrastructureService $settingsInfrastructureService
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            throw new AccessDeniedHttpException('No user.');
        }

        $server->setApiPath('/api/settings/logo-upload/v1/tus');
        $server->getCache()->setPrefix($user->getId());
        $server->setMaxUploadSize(10485760);

        $settingsInfrastructureService->prepareLogoUpload($user, $server);

        $server->event()->addListener(
            UploadComplete::NAME,
            function (UploadComplete $event)
            use ($logger, $settingsInfrastructureService, $user, $token, $server)
            {
                $fileMeta = $event->getFile()->details();

                $settingsInfrastructureService
                    ->handleCompletedLogoUpload(
                        $user,
                        $token,
                        $event
                    );

                $logger->debug("fileMeta: " . json_encode($fileMeta));
            }
        );

        return $server->serve();
    }
}
