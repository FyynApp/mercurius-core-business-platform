<?php

namespace App\VideoBasedMarketing\Settings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomLogoSetting;
use App\VideoBasedMarketing\Settings\Infrastructure\Entity\LogoUpload;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class SettingsDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * @throws Exception
     */
    private function getCustomLogoSetting(
        User $user
    ): CustomLogoSetting
    {
        if (is_null($user->getCustomLogoSetting())) {
            $customLogoSetting = new CustomLogoSetting($user);

            $this->entityManager->persist($customLogoSetting);
            $this->entityManager->flush($customLogoSetting);

            $this->entityManager->refresh($user);
        }

        return $user->getCustomLogoSetting();
    }

    public function makeLogoUploadActive(
        LogoUpload $logoUpload
    ): void
    {
        foreach ($logoUpload->getUser()->getLogoUploads() as $existingLogoUpload) {
            $existingLogoUpload->setCustomLogoSetting(null);
        }

        $customLogoSetting = $this->getCustomLogoSetting($logoUpload->getUser());

        $customLogoSetting->setLogoUpload($logoUpload);
        $logoUpload->setCustomLogoSetting($customLogoSetting);

        $this->entityManager->persist($logoUpload);
        $this->entityManager->persist($customLogoSetting);

        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function getCustomDomainSetting(
        User $user
    ): CustomDomainSetting
    {
        if (is_null($user->getCustomDomainSetting())) {
            $customDomainSetting = new CustomDomainSetting($user);

            $this->entityManager->persist($customDomainSetting);
            $this->entityManager->flush($customDomainSetting);

            $this->entityManager->refresh($user);
        }

        return $user->getCustomDomainSetting();
    }
}
