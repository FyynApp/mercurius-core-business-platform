<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Service;

use App\VideoBasedMarketing\Membership\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Exception;
use Twig\Environment;

readonly class EmbeddableVideoPlayerPresentationService
{
    public function __construct(
        private Environment       $twigEnvironment,
        private MembershipService $membershipService
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
        return !$this->membershipService->subscriptionOfOrganizationOwnedEntityHasCapability(
            $video,
            Capability::BrandingFreeEmbeddableVideoPlayer
        );
    }
}
