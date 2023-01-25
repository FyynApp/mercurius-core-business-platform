<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class RecordingsAdminController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.admin.en%/recordings/videos/',
            'de' => '%app.routing.route_prefix.with_locale.admin.de%/aufnahmen/videos/',
        ],
        name        : 'videobasedmarketing.recordings.presentation.admin.videos.overview',
        methods     : [Request::METHOD_GET]
    )]
    public function videosOverviewAction(
        VideoDomainService $videoDomainService
    ): Response
    {
        $videos = $videoDomainService->getNewestVideos();

        return $this->render(
            '@videobasedmarketing.recordings/admin/videos_overview.html.twig',
            ['videos' => $videos]
        );
    }
}
