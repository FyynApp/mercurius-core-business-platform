<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;


readonly class VideoFolderDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function createVideoFolder(
        User         $user,
        string       $name,
        ?VideoFolder $parentVideoFolder
    ): ?VideoFolder
    {
        if (!is_null($parentVideoFolder)) {
            if ($parentVideoFolder->getOrganization()->getId() !== $user->getCurrentlyActiveOrganization()->getId()) {
                return null;
            }
        }

        $videoFolder = new VideoFolder($user, $name);
        $videoFolder->setParentVideoFolder($parentVideoFolder);

        $this->entityManager->persist($videoFolder);
        $this->entityManager->flush();

        return $videoFolder;
    }

    /**
     * @return VideoFolder[]
     * @throws Exception
     */
    public function getAvailableVideoFoldersForCurrentlyActiveOrganization(
        User $user,
        ?VideoFolder $parentVideoFolder
    ): array
    {
        /** @var ObjectRepository<Video> $repo */
        $repo = $this->entityManager->getRepository(VideoFolder::class);

        if (!is_null($parentVideoFolder)) {
            if ($user->getCurrentlyActiveOrganization()->getId() !== $parentVideoFolder->getOrganization()->getId()) {
                throw new Exception(
                    "User '{$user->getId()}' and video folder '{$parentVideoFolder->getId()}' do not belong to the same organization."
                );
            }
        }

        return $repo->findBy(
            [
                'organization' => $user->getCurrentlyActiveOrganization()->getId(),
                'parentVideoFolder' => $parentVideoFolder
            ],
            ['name' => Criteria::ASC]
        );
    }

    /**
     * @throws Exception
     */
    public function moveVideoIntoFolder(
        Video        $video,
        ?VideoFolder $videoFolder
    ): void
    {
        if (!is_null($videoFolder)) {
            if ($video->getOrganization()->getId() !== $videoFolder->getOrganization()->getId()) {
                throw new Exception('Not the same organization.');
            }
        }

        $video->setVideoFolder($videoFolder);
        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }
}
