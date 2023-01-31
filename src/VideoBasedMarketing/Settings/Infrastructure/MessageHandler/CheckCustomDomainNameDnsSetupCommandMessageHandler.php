<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainDnsSetupStatus;
use App\VideoBasedMarketing\Settings\Infrastructure\Message\CheckCustomDomainNameDnsSetupCommandMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class CheckCustomDomainNameDnsSetupCommandMessageHandler
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(
        CheckCustomDomainNameDnsSetupCommandMessage $message
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

        $customDomainSetting->setDnsSetupStatus(CustomDomainDnsSetupStatus::CheckRunning);
        $this->entityManager->persist($customDomainSetting);
        $this->entityManager->flush();

        $cnameRecord = dns_get_record(
            $customDomainSetting->getDomainName(),
            DNS_CNAME
        );

        if (   is_array($cnameRecord)
            && array_key_exists(0, $cnameRecord)
            && array_key_exists('target', $cnameRecord[0])
            && $cnameRecord[0]['target'] === "customdomain.{$_ENV['APP_ENV']}.fyyn.io"
        ) {
            $customDomainSetting->setDnsSetupStatus(CustomDomainDnsSetupStatus::CheckPositive);
            $this->entityManager->persist($customDomainSetting);
            $this->entityManager->flush();
            return;
        }

        $customDomainSetting->setDnsSetupStatus(CustomDomainDnsSetupStatus::CheckNegative);
        $this->entityManager->persist($customDomainSetting);
        $this->entityManager->flush();
    }
}
