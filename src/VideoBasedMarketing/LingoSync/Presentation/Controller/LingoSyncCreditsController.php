<?php

namespace App\VideoBasedMarketing\LingoSync\Presentation\Controller;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncCreditsDomainService;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Presentation\Controller\VideoFoldersController;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class LingoSyncCreditsController
    extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/buy-lingo-sync-minutes',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/lingo-sync-minuten-kaufen',
        ],
        name        : 'videobasedmarketing.lingo_sync.presentation.buy_credits',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function buyCreditsAction(
        CapabilitiesService $capabilitiesService
    ): Response
    {
        $user = $this->getUser();

        if (!$capabilitiesService->canPurchasePackages($user)) {
            throw new AccessDeniedHttpException('The user is not allowed to purchase packages.');
        }

        return $this->render('@videobasedmarketing.lingo_sync/buy_credits.html.twig');
    }
}
