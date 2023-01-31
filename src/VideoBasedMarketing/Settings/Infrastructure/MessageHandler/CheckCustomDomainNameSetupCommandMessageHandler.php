<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainDnsSetupStatus;
use App\VideoBasedMarketing\Settings\Infrastructure\Message\CheckCustomDomainNameSetupCommandMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class CheckCustomDomainNameSetupCommandMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface    $messageBus
    )
    {
    }

    public function __invoke(
        CheckCustomDomainNameSetupCommandMessage $message
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

            $fs = new Filesystem();
            $fs->mkdir('/var/tmp/mercurius-core-business-platform/customdomain_setup_tasks');
            file_put_contents(
                "/var/tmp/mercurius-core-business-platform/customdomain_setup_tasks/{$customDomainSetting->getId()}",
                $customDomainSetting->getDomainName()
            );

            $this->messageBus->dispatch(
                new CheckCustomDomainNameSetupCommandMessage($customDomainSetting)
            );

            return;
        }

        $customDomainSetting->setDnsSetupStatus(CustomDomainDnsSetupStatus::CheckNegative);
        $this->entityManager->persist($customDomainSetting);
        $this->entityManager->flush();
    }
}
