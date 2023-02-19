<?php

namespace App\VideoBasedMarketing\Settings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
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
        private EntityManagerInterface $entityManager,
        private MessageBusInterface    $messageBus
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
        $this->entityManager->persist($user->getCustomDomainSetting());
        $this->entityManager->flush();

        $this->triggerDomainNameCheck($user);

        return SetCustomDomainNameResult::Success;
    }

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

        $this->entityManager->persist($user->getCustomDomainSetting());
        $this->entityManager->flush();

        $this->messageBus->dispatch(
            new CheckCustomDomainNameSetupCommandMessage(
                $user->getCustomDomainSetting()
            )
        );
    }

    public function getUsableCustomDomain(User $user): ?string
    {
        if (   !is_null($user->getCustomDomainSetting())
            && !is_null($user->getCustomDomainSetting()->getDomainName())
            && $user->getCustomDomainSetting()->getDnsSetupStatus() === CustomDomainDnsSetupStatus::CheckPositive
            && $user->getCustomDomainSetting()->getHttpSetupStatus() === CustomDomainHttpSetupStatus::CheckPositive
        ) {
            return $user->getCustomDomainSetting()->getDomainName();
        }

        return null;
    }
}
