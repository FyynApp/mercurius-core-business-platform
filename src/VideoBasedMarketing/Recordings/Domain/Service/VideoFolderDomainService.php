<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use Doctrine\ORM\EntityManagerInterface;
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
}
