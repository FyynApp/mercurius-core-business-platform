<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Settings\Domain\Service\SettingsDomainService;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class RecordingsPresentationService
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
    public function getVideoShareLinkUrl(
        Video $video
    ): string
    {
        $locale = Iso639_1Code::En->value;
        $request = $this->requestStack->getCurrentRequest();
        if (!is_null($request)) {
             $requestLocale = Iso639_1Code::tryFrom($request->getLocale());
             if (!is_null($requestLocale)) {
                 $locale = $requestLocale->value;
             }
        }

        if ($this
            ->capabilitiesService
            ->canPresentLandingpageOnCustomDomain($video->getUser())
            &&
            !is_null($this
                ->settingsDomainService
                ->getUsableCustomDomain($video->getUser())
            )
        ) {
            return 'https://'
                . $this->settingsDomainService->getUsableCustomDomain($video->getUser())
                . $this->router->generate(
                    'videobasedmarketing.recordings.presentation.video.share_link',
                    [
                        'videoShortId' => $this->shortIdService->encodeObject($video),
                        '_locale' => $locale
                    ]
                );

        } else {
            return $this->router->generate(
                'videobasedmarketing.recordings.presentation.video.share_link',
                [
                    'videoShortId' => $this->shortIdService->encodeObject($video),
                    '_locale' => $locale
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }
    }
}
