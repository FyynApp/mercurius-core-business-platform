<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Service;

use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Membership\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipPlanService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Exception;
use Twig\Environment;

readonly class EmbeddableVideoPlayerPresentationService
{
    public function __construct(
        private Environment       $twigEnvironment,
        private MembershipPlanService $membershipPlanService,
        private ShortIdService    $shortIdService
    )
    {}

    /**
     * @throws Exception
     */
    public function getVideoEmbedCode(
        Video $video,
        bool  $autoplay = false
    ): string
    {
        if (is_null($video->getShortId())) {
            $this->shortIdService->encodeObject($video);
        }

        return $this->twigEnvironment->render(
            '@videobasedmarketing.recordings/embeddable_video_player/embed.html.twig',
            [
                'video' => $video,
                'autoplay' => $autoplay
            ]
        );
    }

    public function embedMustBeBranded(
        Video $video
    ): bool
    {
        return !$this->membershipPlanService->subscriptionOfOrganizationOwnedEntityHasCapability(
            $video,
            Capability::BrandingFreeEmbeddableVideoPlayer
        );
    }
}
