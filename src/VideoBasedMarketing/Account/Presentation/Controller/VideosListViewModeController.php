<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use App\VideoBasedMarketing\Recordings\Presentation\Controller\VideoFoldersController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;


class VideosListViewModeController
    extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface    $entityManager,
        private readonly OrganizationDomainService $organizationDomainService
    )
    {
        parent::__construct(
            $this->entityManager,
            $this->organizationDomainService
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/account/switch-videos-list-view-mode',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/benutzerkonto/video-listen-anzeige-modus-umschalten',
        ],
        name        : 'videobasedmarketing.account.presentation.switch_videos_list_view_mode',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function switchVideosListViewModeAction(
        Request              $request,
        AccountDomainService $accountDomainService
    ): Response
    {
        if (!$this->isCsrfTokenValid('switch-videos-list-view-mode', $request->get('_csrf_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();

        $accountDomainService->switchVideosListViewMode($user);

        return $this->redirectToRoute(
            'videobasedmarketing.recordings.presentation.videos.overview',
            [
                'q' => trim($request->get('q', '')),
                'videoFolderId' => trim(
                    $request->get(VideoFoldersController::VIDEO_FOLDER_ID_REQUEST_PARAM_NAME)
                ) === ''
                    ? null
                    : $request->get(VideoFoldersController::VIDEO_FOLDER_ID_REQUEST_PARAM_NAME)
            ]
        );
    }
}
