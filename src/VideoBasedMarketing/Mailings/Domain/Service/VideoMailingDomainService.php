<?php

namespace App\VideoBasedMarketing\Mailings\Domain\Service;

use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;

readonly class VideoMailingDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function createVideoMailing(Video $video): VideoMailing
    {
        $videoMailing = new VideoMailing($video->getUser(), $video);
        $this->entityManager->persist($videoMailing);
        $this->entityManager->flush();

        return $videoMailing;
    }
}
