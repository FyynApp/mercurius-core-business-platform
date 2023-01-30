<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use App\VideoBasedMarketing\Settings\Domain\Enum\DomainCheckStatus;
use App\VideoBasedMarketing\Settings\Infrastructure\Message\CheckCustomDomainNameCommandMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class CheckCustomDomainNameCommandMessageHandler
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(
        CheckCustomDomainNameCommandMessage $message
    ): void
    {
        /** @var null|CustomDomainSetting $customDomainSetting */
        $customDomainSetting = $this->entityManager->find(
            CustomDomainSetting::class,
            $message->getCustomDomainSettingId()
        );

        if (is_null($customDomainSetting)) {
            throw new UnrecoverableMessageHandlingException(
                "Could not find customDomainSetting with id '{$message->getCustomDomainSettingId()}'."
            );
        }

        $customDomainSetting->setCheckStatus(DomainCheckStatus::CheckRunning);
        $this->entityManager->persist($customDomainSetting);
        $this->entityManager->flush();

        $aRecord = dns_get_record(
            $customDomainSetting->getDomainName(),
            DNS_A
        );

        if (   is_array($aRecord)
            && array_key_exists(0, $aRecord)
            && array_key_exists('ip', $aRecord[0])
            && $aRecord[0]['ip'] === '138.201.225.175'
        ) {
            $customDomainSetting->setCheckStatus(DomainCheckStatus::CheckPositive);
            $this->entityManager->persist($customDomainSetting);
            $this->entityManager->flush();
            return;
        }


        $cnameRecord = dns_get_record(
            $customDomainSetting->getDomainName(),
            DNS_CNAME
        );

        if (   is_array($cnameRecord)
            && array_key_exists(0, $cnameRecord)
            && array_key_exists('target', $cnameRecord[0])
            && $cnameRecord[0]['target'] === 'app.fyyn.io'
        ) {
            $customDomainSetting->setCheckStatus(DomainCheckStatus::CheckPositive);
            $this->entityManager->persist($customDomainSetting);
            $this->entityManager->flush();
            return;
        }

        $customDomainSetting->setCheckStatus(DomainCheckStatus::CheckNegative);
        $this->entityManager->persist($customDomainSetting);
        $this->entityManager->flush();
    }
}
