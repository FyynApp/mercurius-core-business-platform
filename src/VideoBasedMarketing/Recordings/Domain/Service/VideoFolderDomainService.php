<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use ValueError;


readonly class VideoFolderDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CapabilitiesService    $capabilitiesService
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
        User         $user,
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

        /** @var VideoFolder[] $videoFolders */
        $videoFolders = $repo->findBy(
            [
                'organization' => $user->getCurrentlyActiveOrganization()->getId(),
                'parentVideoFolder' => $parentVideoFolder
            ],
            ['name' => Criteria::ASC]
        );

        if ($this->capabilitiesService->canSeeFoldersNotVisibleForNonAdministrators($user)) {
            return $videoFolders;
        } else {
            $results = [];
            foreach ($videoFolders as $videoFolder) {
                if ($videoFolder->getIsVisibleForNonAdministrators()) {
                    $results[] = $videoFolder;
                }
            }
            return $results;
        }
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

    /**
     * @throws Exception
     */
    public function getNumberOfVideosInFolder(
        ?VideoFolder $videoFolder,
        Organization $organization
    ): int
    {
        if (!is_null($videoFolder)) {
            if ($videoFolder->getOrganization()->getId() !== $organization->getId()) {
                throw new ValueError(
                    "Video folder '{$videoFolder->getId()}' does not belong to organization '{$organization->getId()}'."
                );
            }
        }

        /** @var ObjectRepository<Video> $videoRepo */
        $videoRepo = $this
            ->entityManager
            ->getRepository(Video::class);

        if (is_null($videoFolder)) {
            return sizeof(
                $videoRepo->findBy(
                    [
                        'organization' => $organization->getId(),
                        'isDeleted'    => false
                    ]
                )
            );
        }

        /** @var ObjectRepository<VideoFolder> $videoFolderRepo */
        $videoFolderRepo = $this
            ->entityManager
            ->getRepository(VideoFolder::class);


        $count = sizeof(
            $videoRepo
                ->findBy(['videoFolder' => $videoFolder->getId()]));

        /** @var VideoFolder[] $childFolders */
        $childFolders = $videoFolderRepo
            ->findBy(['parentVideoFolder' => $videoFolder->getId()]);

        foreach ($childFolders as $childFolder) {
            $count += $this->getNumberOfVideosInFolder($childFolder, $organization);
        }

        return $count;
    }

    public function deleteFolder(
        VideoFolder $videoFolder
    ): void
    {
        /** @var ObjectRepository<VideoFolder> $repo */
        $repo = $this->entityManager->getRepository(VideoFolder::class);

        /** @var VideoFolder[] $videoFolders */
        $videoFolders = $repo->findBy(
            ['parentVideoFolder' => $videoFolder->getId()]
        );

        foreach ($videoFolders as $childVideoFolder) {
            $this->deleteFolder($childVideoFolder);
        }


        /** @var ObjectRepository<Video> $repo */
        $repo = $this->entityManager->getRepository(Video::class);

        /** @var Video[] $videos */
        $videos = $repo->findBy(
            ['videoFolder' => $videoFolder->getId()]
        );

        foreach ($videos as $video) {
            $video->setVideoFolder($videoFolder->getParentVideoFolder());
            $this->entityManager->persist($video);
        }

        $this->entityManager->remove($videoFolder);
        $this->entityManager->flush();
    }

    public function setIsVisibleForNonAdministrators(
        VideoFolder $videoFolder,
        bool        $isVisible
    ): void
    {
        $videoFolder->setIsVisibleForNonAdministrators($isVisible);
        $this->entityManager->persist($videoFolder);
        $this->entityManager->flush();
    }

    public function setIsDefaultForAdministratorRecordings(
        VideoFolder $videoFolder
    ): void
    {
        /** @var ObjectRepository<Video> $repo */
        $repo = $this->entityManager->getRepository(VideoFolder::class);

        /** @var VideoFolder[] $folders */
        $folders = $repo->findBy(
            [
                'organization' => $videoFolder->getOrganization()->getId()
            ],
            ['name' => Criteria::ASC]
        );

        foreach ($folders as $folder) {
            if ($folder->getIsDefaultForAdministratorRecordings() === true) {
                $folder->setIsDefaultForAdministratorRecordings(false);
                $this->entityManager->persist($folder);
                $this->entityManager->flush();
                break;
            }
        }

        $videoFolder->setIsDefaultForAdministratorRecordings(true);
        $this->entityManager->persist($videoFolder);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function getDefaultFolderForNewRecordings(
        User $user
    ): ?VideoFolder
    {
        if (!$this->capabilitiesService->canStoreNewRecordingsInDefaultFolderForAdministratorRecordings($user)) {
            return null;
        }

        foreach ($this->getAvailableVideoFoldersForCurrentlyActiveOrganization($user, null) as $videoFolder) {
            if ($videoFolder->getIsDefaultForAdministratorRecordings() === true) {
                return $videoFolder;
            }
        }

        return null;
    }
}
