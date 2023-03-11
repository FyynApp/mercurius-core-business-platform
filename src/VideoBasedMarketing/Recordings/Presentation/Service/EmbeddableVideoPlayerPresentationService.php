<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Service;

use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Settings\Domain\Service\SettingsDomainService;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

readonly class EmbeddableVideoPlayerPresentationService
{
    public function __construct(
        private CapabilitiesService   $capabilitiesService,
        private SettingsDomainService $settingsDomainService,
        private RouterInterface       $router,
        private RequestStack          $requestStack,
        private ShortIdService        $shortIdService
    )
    {}

    /**
     * @throws Exception
     */
    public function getVideoEmbedCode(
        Video $video
    ): string
    {
        return '<!-- embed code -->';
    }
}
