<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Service;

use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Exception;
use Twig\Environment;

readonly class EmbeddableVideoPlayerPresentationService
{
    public function __construct(
        private Environment $twigEnvironment
    )
    {}

    /**
     * @throws Exception
     */
    public function getVideoEmbedCode(
        Video $video
    ): string
    {
        return $this->twigEnvironment->render(
            '@videobasedmarketing.recordings/embeddable_video_player/embed.html.twig',
            ['video' => $video]
        );
    }
}
