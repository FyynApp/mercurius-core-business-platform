<?php

namespace App\VideoBasedMarketing\Settings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomLogoSetting;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainDnsSetupStatus;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainHttpSetupStatus;
use App\VideoBasedMarketing\Settings\Domain\Enum\SetCustomDomainNameResult;
use App\VideoBasedMarketing\Settings\Infrastructure\Entity\LogoUpload;
use App\VideoBasedMarketing\Settings\Infrastructure\Message\CheckCustomDomainNameSetupCommandMessage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class SettingsDomainService
{
    public function __construct(
        private EntityManagerInterface    $entityManager,
        private MessageBusInterface       $messageBus,
        private OrganizationDomainService $organizationDomainService
    )
    {
    }

    /**
     * @throws Exception
     */
    public function getCustomLogoSetting(
        User $user
    ): CustomLogoSetting
    {
        $organization = $this
            ->organizationDomainService
            ->getOrganizationOfUser($user);

        if (is_null($organization->getCustomLogoSetting())) {
            $customLogoSetting = new CustomLogoSetting($organization);

            $this->entityManager->persist($customLogoSetting);
            $this->entityManager->flush($customLogoSetting);

            $this->entityManager->refresh($organization);
        }

        return $organization->getCustomLogoSetting();
    }

    /**
     * @throws Exception
     */
    public function makeLogoUploadActive(
        LogoUpload $logoUpload
    ): void
    {
        foreach ($logoUpload->getOrganization()->getLogoUploads() as $existingLogoUpload) {
            $existingLogoUpload->setCustomLogoSetting(null);
        }

        $customLogoSetting = $this->getCustomLogoSetting($logoUpload->getOrganization()->getOwningUser());

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
        $organization = $this
            ->organizationDomainService
            ->getOrganizationOfUser($user);

        if (is_null($organization->getCustomDomainSetting())) {
            $customDomainSetting = new CustomDomainSetting($organization);

            $this->entityManager->persist($customDomainSetting);
            $this->entityManager->flush($customDomainSetting);

            $this->entityManager->refresh($organization);
        }

        return $organization->getCustomDomainSetting();
    }

    /**
     * @throws Exception
     */
    public function setCustomDomainName(
        User   $user,
        string $domainName
    ): SetCustomDomainNameResult
    {
        $domainName = trim(mb_strtolower($domainName));

        if (!$user->isAdmin()) {
            if (mb_substr($domainName, -8) === '.fyyn.io') {
                return SetCustomDomainNameResult::IsMercuriusDomain;
            }

            if (mb_substr($domainName, -9) === '.fyyn.app') {
                return SetCustomDomainNameResult::IsMercuriusDomain;
            }
        }

        if (!mb_ereg(
            '^(((?!\-))(xn\-\-)?[a-z0-9\-_]{0,61}[a-z0-9]{1,1}\.)*(xn\-\-)?([a-z0-9\-]{1,61}|[a-z0-9\-]{1,30})\.[a-z]{2,}$',
            $domainName
        )) {

            // Maybe it's a URL, not a domain...
            $urlParts = parse_url($domainName);

            if (   is_array($urlParts)
                && array_key_exists('host', $urlParts)
            ) {
                $domainName = $urlParts['host'];

                if (!mb_ereg(
                    '^(((?!\-))(xn\-\-)?[a-z0-9\-_]{0,61}[a-z0-9]{1,1}\.)*(xn\-\-)?([a-z0-9\-]{1,61}|[a-z0-9\-]{1,30})\.[a-z]{2,}$',
                    $domainName
                )) {
                    return SetCustomDomainNameResult::InvalidDomainName;
                }
            } else {
                return SetCustomDomainNameResult::InvalidDomainName;
            }
        }

        if (mb_substr_count($domainName, '.') === 1) {
            return SetCustomDomainNameResult::IsApexDomain;
        }

        if ($domainName === $this->getCustomDomainSetting($user)->getDomainName()) {
            return SetCustomDomainNameResult::Success;
        }

        $this->getCustomDomainSetting($user)->setDomainName($domainName);
        $this->entityManager->persist($this->getCustomDomainSetting($user));
        $this->entityManager->flush();

        $this->triggerDomainNameCheck($user);

        return SetCustomDomainNameResult::Success;
    }

    /**
     * @throws Exception
     */
    public function triggerDomainNameCheck(
        User $user
    ): void
    {
        $this
            ->getCustomDomainSetting($user)
            ->setDnsSetupStatus(CustomDomainDnsSetupStatus::CheckOutstanding);

        $this
            ->getCustomDomainSetting($user)
            ->setHttpSetupStatus(CustomDomainHttpSetupStatus::CheckOutstanding);

        $this->entityManager->persist($this->getCustomDomainSetting($user));
        $this->entityManager->flush();

        $this->messageBus->dispatch(
            new CheckCustomDomainNameSetupCommandMessage(
                $this->getCustomDomainSetting($user)
            )
        );
    }

    /**
     * @throws Exception
     */
    public function getUsableCustomDomain(User $user): ?string
    {
        if (   !is_null($this->getCustomDomainSetting($user))
            && !is_null($this->getCustomDomainSetting($user)->getDomainName())
            && $this->getCustomDomainSetting($user)->getDnsSetupStatus() === CustomDomainDnsSetupStatus::CheckPositive
            && $this->getCustomDomainSetting($user)->getHttpSetupStatus() === CustomDomainHttpSetupStatus::CheckPositive
        ) {
            return $this->getCustomDomainSetting($user)->getDomainName();
        }

        return null;
    }

    public function userCanChangeCustomDomainSetting(
        User $user
    ): bool
    {
        return true;
    }

    public function userCanChangeCustomLogoSetting(
        User $user
    ): bool
    {
        return true;
    }
}
